<?php
/**
 * @file Activation.php
 * This file provides a class to represent an Activation record.
 *
 */
namespace JackBradford\Disphatch\Etc;;

/**
 * @class Activation
 * This class provides a means of interacting with Activation records 
 * independent of the third-party user management library. That is, if that
 * library is ever replaced with a different one, a new implementation of this
 * class can be added, as opposed to finding every place in the codebase where
 * the first library was called and changing the implementation in each of
 * those places.
 *
 */
class Activation {

    private $code;
    private $userId;
    private $createdAt;
    private $updatedAt;
    private $id;

    /**
     * @method Activation::__construct
     * Create an instance of the Activation class, which represnets a User
     * Activation record.
     *
     * @param array $data
     *  ['code']        The activation code.
     *  ['userId']      The ID of the user being activated.
     *  ['createdAt']   The time of the activation record's creation.
     *  ['updatedAt']   The time of the activation record's last update.
     *  ['id']          The ID of the activation record.
     *
     * @return Activation
     */
    public function __construct(array $data) {

        if (!$this->validateConstructorInput($data)) {

            throw new \Exception(__METHOD__ . ': Missings argument(s).');
        }

        $this->code = $data['code'];
        $this->userId = $data['userId'];
        $this->createdAt = $data['createdAt'];
        $this->updatedAt = $data['updatedAt'];
        $this->id = $data['id'];
    }

    /**
     * @method Activation::getDetails
     * Get the Activation record's data.
     *
     * @return obj
     */
    public function getDetails() {

        return (object) [

            'code' => $this->code,
            'userId' => $this->userId,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'id' => $this->id,
        ];
    }

    private function validateConstructorInput(array $data) {

        if (!isset($data['code'])) return false;
        if (!isset($data['userId'])) return false;
        if (!isset($data['createdAt'])) return false;
        if (!isset($data['updatedAt'])) return false;
        if (!isset($data['id'])) return false;
        return true;
    }
}

