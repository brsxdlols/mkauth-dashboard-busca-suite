<?php

if (!class_exists('MkaRouterosMiniApi')) {
    class MkaRouterosMiniApi
    {
        private $socket = null;
        private $timeout;

        public function __construct($timeout = 5)
        {
            $this->timeout = (int) $timeout;
        }

        public function connect($host, $user, $pass, $port = 8728)
        {
            $errno = 0;
            $errstr = '';
            $this->socket = @fsockopen($host, $port, $errno, $errstr, $this->timeout);
            if (!$this->socket) return false;
            stream_set_timeout($this->socket, $this->timeout);

            // Login moderno (RouterOS 6.43+).
            $this->writeSentence(array('/login', '=name=' . $user, '=password=' . $pass));
            $reply = $this->readReply();
            if ($this->hasDone($reply)) return true;

            // Compatibilidade com o desafio utilizado por versões antigas.
            $challenge = $this->findRet($reply);
            if ($challenge !== '') {
                $response = '00' . md5(chr(0) . $pass . pack('H*', $challenge));
                $this->writeSentence(array('/login', '=name=' . $user, '=response=' . $response));
                $reply = $this->readReply();
                if ($this->hasDone($reply)) return true;
            }

            $this->disconnect();
            return false;
        }

        public function disconnect()
        {
            if ($this->socket) fclose($this->socket);
            $this->socket = null;
        }

        private function hasDone($reply)
        {
            foreach ($reply as $sentence) {
                if (isset($sentence[0]) && $sentence[0] === '!done') return true;
            }
            return false;
        }

        private function findRet($reply)
        {
            foreach ($reply as $sentence) {
                foreach ($sentence as $word) {
                    if (strpos($word, '=ret=') === 0) return substr($word, 5);
                }
            }
            return '';
        }

        private function writeSentence($words)
        {
            foreach ($words as $word) $this->writeWord($word);
            $this->writeWord('');
        }

        private function writeWord($word)
        {
            $len = strlen($word);
            if ($len < 0x80) {
                fwrite($this->socket, chr($len));
            } elseif ($len < 0x4000) {
                fwrite($this->socket, chr(($len >> 8) | 0x80) . chr($len & 0xFF));
            } elseif ($len < 0x200000) {
                fwrite($this->socket, chr(($len >> 16) | 0xC0) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
            } else {
                fwrite($this->socket, chr(($len >> 24) | 0xE0) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
            }
            if ($len > 0) fwrite($this->socket, $word);
        }

        private function readReply()
        {
            $reply = array();
            $sentence = array();
            while (true) {
                $word = $this->readWord();
                if ($word === false) break;
                if ($word === '') {
                    if ($sentence) {
                        $reply[] = $sentence;
                        if (isset($sentence[0]) && ($sentence[0] === '!done' || $sentence[0] === '!fatal')) break;
                        $sentence = array();
                    }
                    continue;
                }
                $sentence[] = $word;
            }
            return $reply;
        }

        private function readWord()
        {
            $len = $this->readLength();
            if ($len === false) return false;
            if ($len === 0) return '';
            $data = '';
            while (strlen($data) < $len) {
                $chunk = fread($this->socket, $len - strlen($data));
                if ($chunk === false || $chunk === '') return false;
                $data .= $chunk;
            }
            return $data;
        }

        private function readLength()
        {
            $raw = fread($this->socket, 1);
            if ($raw === false || $raw === '') return false;
            $first = ord($raw);
            if (($first & 0x80) === 0x00) return $first;
            if (($first & 0xC0) === 0x80) return (($first & ~0xC0) << 8) + ord(fread($this->socket, 1));
            if (($first & 0xE0) === 0xC0) return (($first & ~0xE0) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
            if (($first & 0xF0) === 0xE0) return (($first & ~0xF0) << 24) + (ord(fread($this->socket, 1)) << 16) + (ord(fread($this->socket, 1)) << 8) + ord(fread($this->socket, 1));
            return false;
        }
    }
}

