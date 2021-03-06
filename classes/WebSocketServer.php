<?php

abstract class WebSocketServer
{

    protected $userClass = 'WebSocketUser'; // redefine this if you want a custom user class.  The custom user class should inherit from WebSocketUser.
    protected $maxBufferSize;
    protected $master;
    protected $sockets = array();
    protected $users = array();
    protected $interactive = true;
    protected $headerOriginRequired = false;
    protected $headerSecWebSocketProtocolRequired = false;
    protected $headerSecWebSocketExtensionsRequired = false;

    public function __construct($addr, $port, $bufferLength = 2048)
    {
        $this->maxBufferSize = $bufferLength;
        try {
            $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        } catch (Exception $e) {
            $this->stdout($e . 'Failed: socket_create()');
        }
        try {
            socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1);
        } catch (Exception $e) {
            $this->stdout($e . 'Failed: socket_option()');
        }
        try {
            socket_bind($this->master, $addr, $port);
        } catch (Exception $e) {
            $this->stdout($e . 'Failed: socket_bind()');
        }
        try {
            socket_listen($this->master, 20);
        } catch (Exception $e) {
            $this->stdout($e . 'Failed: socket_listen()');
        }
        $this->sockets['m'] = $this->master;
        $this->stdout("Server started\nListening on: $addr:$port\nMaster socket: " . $this->master);
    }

    public function stdout($message)
    {
        if ($this->interactive) {
            echo "$message\n";
        }
    } // Called immediately when the data is recieved.

    /**
     * Main processing loop
     */
    public function run()
    {
        while (true) {
            if (0 === count($this->sockets)) {
                $this->sockets['m'] = $this->master;
            }
            $read = $this->sockets;
            $write = $except = null;
            @socket_select($read, $write, $except, null);
            foreach ($read as $socket) {
                if ($socket === $this->master) {
                    $client = socket_accept($socket);
                    if ($client < 0) {
                        $this->stderr('Failed: socket_accept()');
                        continue;
                    } else {
                        $this->connect($client);
                        $this->stdout('Client connected. ' . $client);
                    }
                } else {
                    $numBytes = @socket_recv($socket, $buffer, $this->maxBufferSize, 0);
                    if ($numBytes === false) {
                        $sockErrNo = socket_last_error($socket);
                        switch ($sockErrNo) {
                            case 102: // ENETRESET    -- Network dropped connection because of reset
                            case 103: // ECONNABORTED -- Software caused connection abort
                            case 104: // ECONNRESET   -- Connection reset by peer
                            case 108: // ESHUTDOWN    -- Cannot send after transport endpoint shutdown -- probably more of an error on our part, if we're trying to write after the socket is closed.  Probably not a critical error, though.
                            case 110: // ETIMEDOUT    -- Connection timed out
                            case 111: // ECONNREFUSED -- Connection refused -- We shouldn't see this one, since we're listening... Still not a critical error.
                            case 112: // EHOSTDOWN    -- Host is down -- Again, we shouldn't see this, and again, not critical because it's just one connection and we still want to listen to/for others.
                            case 113: // EHOSTUNREACH -- No route to host
                            case 121: // EREMOTEIO    -- Rempte I/O error -- Their hard drive just blew up.
                            case 125: // ECANCELED    -- Operation canceled

                            $this->stderr('Unusual disconnect on socket ' . $socket);
                                $this->disconnect($socket, true, $sockErrNo); // disconnect before clearing error, in case someone with their own implementation wants to check for error conditions on the socket.
                                break;
                            default:

                                $this->stderr('Socket error: ' . socket_strerror($sockErrNo));
                        }

                    } elseif ($numBytes === 0) {
                        $this->disconnect($socket);
                        $this->stderr('Client disconnected. TCP connection lost: ' . $socket);
                    } else {
                        $user = $this->getUserBySocket($socket);
                        if (!$user->handshake) {
                            $tmp = str_replace("\r", '', $buffer);
                            if (strpos($tmp, "\n\n") === false) {
                                continue; // If the client has not finished sending the header, then wait before sending our upgrade response.
                            }
                            $this->doHandshake($user, $buffer);
                        } else {
                            //split packet into frame and send it to deframe
                            $this->split_packet($numBytes, $buffer, $user);
                        }
                    }
                }
            }
        }
    }        // Called after the handshake response is sent to the client.

    public function stderr($message)
    {
        if ($this->interactive) {
            echo "$message\n";
        }
    }           // Called after the connection is closed.

    protected function connect($socket)
    {
        $user = new $this->userClass(uniqid('u', false), $socket);
        $this->users[$user->id] = $user;
        $this->sockets[$user->id] = $socket;
        $this->connecting($user);
    }

    protected function connecting($user)
    {
        // Override to handle a connecting user, after the instance of the User is created, but before
        // the handshake has completed.
    }

    protected function disconnect($socket, $triggerClosed = true, $sockErrNo = null)
    {
        $disconnectedUser = $this->getUserBySocket($socket);

        if ($disconnectedUser !== null) {
            unset($this->users[$disconnectedUser->id]);

            if (array_key_exists($disconnectedUser->id, $this->sockets)) {
                unset($this->sockets[$disconnectedUser->id]);
            }

            if (null !== $sockErrNo) {
                socket_clear_error($socket);
            }

            if ($triggerClosed) {
                $this->closed($disconnectedUser);
                socket_close($disconnectedUser->socket);
            } else {
                $message = $this->frame('', $disconnectedUser, 'close');
                @socket_write($disconnectedUser->socket, $message, strlen($message));
            }
        }
    }

    protected function getUserBySocket($socket)
    {
        foreach ($this->users as $user) {
            if ($user->socket === $socket) {
                return $user;
            }
        }
        return null;
    }

    abstract protected function closed($user);

    /**
     * @param $message
     * @param $user
     * @param string $messageType
     * @return string
     * hexdec function may be replaced with intval in future refactoring.
     */
    protected function frame($message, $user, $messageType = 'text')
    {
        $messageContinues = false;
        switch ($messageType) {
            case 'continuous':
                $b1 = 0;
                break;
            case 'text':
                $b1 = ($user->sendingContinuous) ? 0 : 1;
                break;
            case 'binary':
                $b1 = ($user->sendingContinuous) ? 0 : 2;
                break;
            case 'close':
                $b1 = 8;
                break;
            case 'ping':
                $b1 = 9;
                break;
            case 'pong':
                $b1 = 10;
                break;
            default:
                $b1 = 0;
                break;
        }
        if ($messageContinues) {
            $user->sendingContinuous = true;
        } else {
            $b1 += 128;
            $user->sendingContinuous = false;
        }


        $length = strlen($message);
        $lengthField = '';
        if ($length < 126) {
            $b2 = $length;
        } elseif ($length <= 65536) {
            $b2 = 126;
            $hexLength = dechex($length);
            //$this->stdout("Hex Length: $hexLength");
            if (strlen($hexLength) % 2 === 1) {
                $hexLength = '0' . $hexLength;
            }
            $n = strlen($hexLength) - 2;

            for ($i = $n; $i >= 0; $i -= 2) {
                /** @noinspection AliasFunctionsUsageInspection */
                $lengthField = chr(hexdec(substr($hexLength, $i, 2))) . $lengthField;
            }
            while (strlen($lengthField) < 2) {
                $lengthField = chr(0) . $lengthField;
            }
        } else {
            $b2 = 127;
            $hexLength = dechex($length);
            if (strlen($hexLength) % 2 === 1) {
                $hexLength = '0' . $hexLength;
            }
            $n = strlen($hexLength) - 2;

            for ($i = $n; $i >= 0; $i -= 2) {
                /** @noinspection AliasFunctionsUsageInspection */
                $lengthField = chr(hexdec(substr($hexLength, $i, 2))) . $lengthField;
            }
            while (strlen($lengthField) < 8) {
                $lengthField = chr(0) . $lengthField;
            }
        }

        return chr($b1) . chr($b2) . $lengthField . $message;
    }

    protected function doHandshake($user, $buffer)
    {
        // $magicGUID = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";  // must be defined in config.
        $handshakeResponse = null;
        $headers = array();
        $lines = explode("\n", $buffer);
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                $header = explode(':', $line, 2);
                $headers[strtolower(trim($header[0]))] = trim($header[1]);
            } elseif (stripos($line, 'get ') !== false) {
                preg_match('/GET (.*) HTTP/i', $buffer, $reqResource);
                $headers['get'] = trim($reqResource[1]);
            }
        }
        if (array_key_exists('get', $headers)) {
            $user->requestedResource = $headers['get'];
        } else {
            // todo: fail the connection
            $handshakeResponse = "HTTP/1.1 405 Method Not Allowed\r\n\r\n";
        }
        if (!array_key_exists('host', $headers) || !$this->checkHost($headers['host'])) {
            $handshakeResponse = 'HTTP/1.1 400 Bad Request';
        }
        if (!array_key_exists('upgrade', $headers) || strtolower($headers['upgrade']) !== 'websocket') {
            $handshakeResponse = 'HTTP/1.1 400 Bad Request';
        }
        if (!array_key_exists('connection', $headers) || strpos(strtolower($headers['connection']), 'upgrade') === FALSE) {
            $handshakeResponse = 'HTTP/1.1 400 Bad Request';
        }
        if (!array_key_exists('sec-websocket-key', $headers)) {
            $handshakeResponse = 'HTTP/1.1 400 Bad Request';
        }
        if (!array_key_exists('sec-websocket-version', $headers) || strtolower($headers['sec-websocket-version']) !== '13') {
            $handshakeResponse = "HTTP/1.1 426 Upgrade Required\r\nSec-WebSocketVersion: 13";
        }
        if (($this->headerOriginRequired && !array_key_exists('origin', $headers)) || ($this->headerOriginRequired && !$this->checkOrigin($headers['origin']))) {
            $handshakeResponse = 'HTTP/1.1 403 Forbidden';
        }
        if (($this->headerSecWebSocketProtocolRequired && !array_key_exists('sec-websocket-protocol', $headers)) || ($this->headerSecWebSocketProtocolRequired && !$this->checkWebsocProtocol($headers['sec-websocket-protocol']))) {
            $handshakeResponse = 'HTTP/1.1 400 Bad Request';
        }
        if (($this->headerSecWebSocketExtensionsRequired && !array_key_exists('sec-websocket-extensions', $headers)) || ($this->headerSecWebSocketExtensionsRequired && !$this->checkWebsocExtensions($headers['sec-websocket-extensions']))) {
            $handshakeResponse = 'HTTP/1.1 400 Bad Request';
        }

        // Done verifying the _required_ headers and optionally required headers.

        if (null !== $handshakeResponse) {
            socket_write($user->socket, $handshakeResponse, strlen($handshakeResponse));
            $this->disconnect($user->socket);
            return;
        }

        $user->headers = $headers;
        $user->handshake = $buffer;

        $webSocketKeyHash = sha1($headers['sec-websocket-key'] . MAGIC_GUID);

        $rawToken = '';
        for ($i = 0; $i < 20; $i++) {
            /** @noinspection AliasFunctionsUsageInspection */
            $rawToken .= chr(hexdec(substr($webSocketKeyHash, $i * 2, 2)));
        }
        $handshakeToken = base64_encode($rawToken) . "\r\n";

        $subProtocol = (array_key_exists('sec-websocket-protocol', $headers)) ? $this->processProtocol($headers['sec-websocket-protocol']) : '';
        $extensions = (array_key_exists('sec-websocket-extensions', $headers)) ? $this->processExtensions($headers['sec-websocket-extensions']) : '';

        $handshakeResponse = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: $handshakeToken$subProtocol$extensions\r\n";
        socket_write($user->socket, $handshakeResponse, strlen($handshakeResponse));
        $this->connected($user);
    }

    protected function checkHost($hostName)
    {
        return isset($hostName); // Override and return false if the host is not one that you would expect.
        // Ex: You only want to accept hosts from the my-domain.com domain,
        // but you receive a host from malicious-site.com instead.
    }

    protected function checkOrigin($origin)
    {
        return isset($origin); // Override and return false if the origin is not one that you would expect.
    }

    protected function checkWebsocProtocol($protocol)
    {
        return isset($protocol); // Override and return false if a protocol is not found that you would expect.
    }

    protected function checkWebsocExtensions($extensions)
    {
        return isset($extensions); // Override and return false if an extension is not found that you would expect.
    }

    protected function processProtocol($protocol)
    {
        return isset($protocol) ? $protocol : '';
        // return either "Sec-WebSocket-Protocol: SelectedProtocolFromClientList\r\n" or return an empty string.
        // The carriage return/newline combo must appear at the end of a non-empty string, and must not
        // appear at the beginning of the string nor in an otherwise empty string, or it will be considered part of
        // the response body, which will trigger an error in the client as it will not be formatted correctly.
    }

    protected function processExtensions($extensions)
    {
        return isset($extensions) ? $extensions : ''; // return either "Sec-WebSocket-Extensions: SelectedExtensions\r\n" or return an empty string.
    }

    abstract protected function connected($user);

    protected function split_packet($length, $packet, $user)
    {
        //add PartialPacket and calculate the new $length
        if ($user->handlingPartialPacket) {
            $packet = $user->partialBuffer . $packet;
            $user->handlingPartialPacket = false;
            $length = strlen($packet);
        }
        $fullpacket = $packet;
        $frame_pos = 0;
        $frame_id = 1;

        while ($frame_pos < $length) {
            $headers = $this->extractHeaders($packet);
            $headers_size = $this->calcoffset($headers);
            $framesize = $headers['length'] + $headers_size;
            $this->stdout('frame #' . $frame_id . ' position : ' . $frame_pos . ' msglen : ' . $headers['length'] . ' + headers_size ' . $headers_size . ' = framesize of ' . $framesize);

            //split frame from packet and process it
            $frame = substr($fullpacket, $frame_pos, $framesize);

            if (($message = $this->deframe($frame, $user, $headers)) !== FALSE) {
                if ($user->hasSentClose) {
                    $this->disconnect($user->socket);
                } else {
                    if (function_exists('mb_check_encoding')) {
                        if (mb_check_encoding($message, 'UTF-8')) {
                            $this->process($user, $message);
                        } else {
                            $this->stderr("not UTF-8\n");
                        }
                    } else {
                        $this->process($user, $message);
                    }
                }
            }
            //get the new position also modify packet data
            $frame_pos += $framesize;
            $packet = substr($fullpacket, $frame_pos);
            $frame_id++;
        }
        $this->stdout('########    PACKET END         #########');
    }

    protected function extractHeaders($message)
    {
        $header = array('fin' => $message[0] & chr(128),
            'rsv1' => $message[0] & chr(64),
            'rsv2' => $message[0] & chr(32),
            'rsv3' => $message[0] & chr(16),
            'opcode' => ord($message[0]) & 15,
            'hasmask' => $message[1] & chr(128),
            'length' => 0,
            'mask' => '');
        $header['length'] = (ord($message[1]) >= 128) ? ord($message[1]) - 128 : ord($message[1]);

        if ($header['hasmask'] && $header['length'] === 126) {
            $header['mask'] = $message[4] . $message[5] . $message[6] . $message[7];
        }
        if ($header['hasmask'] && $header['length'] === 127) {
            $header['mask'] = $message[10] . $message[11] . $message[12] . $message[13];
        }
        if ($header['length'] === 126) {
            $header['length'] = ord($message[2]) * 256
                + ord($message[3]);
        } elseif ($header['length'] === 127) {
            $header['length'] = ord($message[2]) * 65536 * 65536 * 65536 * 256
                + ord($message[3]) * 65536 * 65536 * 65536
                + ord($message[4]) * 65536 * 65536 * 256
                + ord($message[5]) * 65536 * 65536
                + ord($message[6]) * 65536 * 256
                + ord($message[7]) * 65536
                + ord($message[8]) * 256
                + ord($message[9]);
        } elseif ($header['hasmask']) {
            $header['mask'] = $message[2] . $message[3] . $message[4] . $message[5];
        }
        //echo $this->strtohex($message);
        //$this->printHeaders($header);
        return $header;
    }

    //check packet if he have more than one frame and process each frame individually

    protected function calcoffset($headers)
    {
        $offset = 2;
        if ($headers['hasmask']) {
            $offset += 4;
        }
        if ($headers['length'] > 65535) {
            $offset += 8;
        } elseif ($headers['length'] > 125) {
            $offset += 2;
        }
        return $offset;
    }

    protected function deframe($message, $user) // todo: analize reference &$user who was previous used.
    {
        //echo $this->strtohex($message);
        $headers = $this->extractHeaders($message);
        $pongReply = false;
        $willClose = false;
        switch ($headers['opcode']) {
            case 0:
            case 1:
            case 2:
                break;
            case 8:
                // todo: close the connection
                $user->hasSentClose = true;
                return '';
            case 9:
                $pongReply = true;
                break;
            case 10:
                break;
            default:
                //$this->disconnect($user); // todo: fail connection
                $willClose = true;
                break;
        }

        /* Deal by split_packet() as now deframe() do only one frame at a time.
        if ($user->handlingPartialPacket) {
          $message = $user->partialBuffer . $message;
          $user->handlingPartialPacket = false;
          return $this->deframe($message, $user);
        }
        */

        /*        if ($this->checkRSVBits($headers, $user)) {     // TODO: workout checkRSVBits
                    return false;
                }*/

        if ($willClose) {
            // todo: fail the connection
            return false;
        }

        $payload = $user->partialMessage . $this->extractPayload($message, $headers);

        if ($pongReply) {
            $reply = $this->frame($payload, $user, 'pong');
            socket_write($user->socket, $reply, strlen($reply));
            return false;
        }
        if (extension_loaded('mbstring')) {
            if ($headers['length'] > mb_strlen($this->applyMask($headers, $payload))) {
                $user->handlingPartialPacket = true;
                $user->partialBuffer = $message;
                return false;
            }
        } else {
            if ($headers['length'] > strlen($this->applyMask($headers, $payload))) {
                $user->handlingPartialPacket = true;
                $user->partialBuffer = $message;
                return false;
            }
        }

        $payload = $this->applyMask($headers, $payload);

        if ($headers['fin']) {
            $user->partialMessage = '';
            return $payload;
        }
        $user->partialMessage = $payload;
        return false;
    }

    protected function extractPayload($message, $headers)
    {
        $offset = 2;
        if ($headers['hasmask']) {
            $offset += 4;
        }
        if ($headers['length'] > 65535) {
            $offset += 8;
        } elseif ($headers['length'] > 125) {
            $offset += 2;
        }
        return substr($message, $offset);
    }

    protected function applyMask($headers, $payload)
    {
        $effectiveMask = '';
        if ($headers['hasmask']) {
            $mask = $headers['mask'];
        } else {
            return $payload;
        }

        while (strlen($effectiveMask) < strlen($payload)) {
            $effectiveMask .= $mask;
        }
        while (strlen($effectiveMask) > strlen($payload)) {
            $effectiveMask = substr($effectiveMask, 0, -1);
        }
        return $effectiveMask ^ $payload;
    }

    abstract protected function process($user, $message);

    protected function checkRSVBits($headers, $user)
    { // override this method if you are using an extension where the RSV bits are used.
        $b = false;
        if (ord($headers['rsv1']) + ord($headers['rsv2']) + ord($headers['rsv3']) > 0) {
            //$this->disconnect($user); // todo: fail connection
            echo 'Disconecting user : ' . $user . "\n";  // temp ref.
            $b = true;
        }
        return $b;
    }

    protected function send($user, $message)
    {
        //$this->stdout("> $message");
        $message = $this->frame($message, $user);
        @socket_write($user->socket, $message, strlen($message));
    }

    protected function printHeaders($headers)
    {
        echo "Array\n(\n";
        foreach ($headers as $key => $value) {
            if ($key === 'length' || $key === 'opcode') {
                echo "\t[$key] => $value\n\n";
            } else {
                echo "\t[$key] => " . $this->strtohex($value) . "\n";

            }

        }
        echo ")\n";
    }

    protected function strtohex($str)
    {
        $strout = '';
        $l = strlen($str);
        for ($i = 0; $i < $l; $i++) {
            $strout .= (ord($str[$i]) < 16) ? '0' . dechex(ord($str[$i])) : dechex(ord($str[$i]));
            $strout .= ' ';
            if ($i % 32 === 7) {
                $strout .= ': ';
            }
            if ($i % 32 === 15) {
                $strout .= ': ';
            }
            if ($i % 32 === 23) {
                $strout .= ': ';
            }
            if ($i % 32 === 31) {
                $strout .= "\n";
            }
        }
        return $strout . "\n";
    }
}
