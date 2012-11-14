<?php

namespace Musicbozz;
use \Ratchet\Wamp\ServerProtocol as ServerProtocolGeneric;
use \Ratchet\ConnectionInterface;

class ServerProtocol extends ServerProtocolGeneric {
	/**
	 * @override
	 */
	public function onOpen(ConnectionInterface $conn) {
        $decor = new Player($conn);
        $this->connections->attach($conn, $decor);

        $this->_decorating->onOpen($decor);
    }
}