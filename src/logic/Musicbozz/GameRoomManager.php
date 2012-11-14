<?php

namespace Musicbozz;

class GameRoomManager extends \Ratchet\Wamp\TopicManager {
	/**
	 * Override
	 */
	protected function getTopic($topic) {
        if (!array_key_exists($topic, $this->topicLookup)) {
            $this->topicLookup[$topic] = new GameRoom($topic);
        }

        return $this->topicLookup[$topic];
    }
}