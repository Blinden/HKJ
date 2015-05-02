<?php

interface EntityInterface
{

    public function hydrate($data);

    public function extract();
}
