<?php

namespace App\Entity\RequestButcher\Factory;

use App\Entity\RequestButcher\Session;
use Symfony\Component\String\ByteString;

class SessionFactory
{
    public function create(string $title, string $description = ''): Session
    {
        return (new Session())
            ->setTitle($title)
            ->setDescription($description)
            ->setSlug(ByteString::fromRandom(8));
    }
}
