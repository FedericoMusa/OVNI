<?php
namespace App\Domain;

class User {
  public function __construct(
    public int $id,
    public string $email,
    public ?string $avatar,
    public string $estado,
    public string $nombreOficina,
    public ?int $idOficina
  ) {}
}
