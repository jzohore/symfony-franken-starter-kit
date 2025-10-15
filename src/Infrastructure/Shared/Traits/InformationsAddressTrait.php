<?php

namespace App\Infrastructure\Shared\Traits;

use App\Domain\Address\ValueObject\AddressType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use libphonenumber\PhoneNumber;

trait InformationsAddressTrait
{
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $address {
        get => $this->address;
        set => $this->address = $value;
    }

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, enumType: AddressType::class)]
    public ?AddressType $type {
        get => $this->type;
        set => $this->type = $value;
    }

    public string $typeValue {
        get => $this->type->value;
    }

    public string $typeLabel {
        get => $this->type->label();
    }

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    public ?string $lastname {
        get => $this->lastname;
        set => $this->lastname = $value;
    }

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    public ?string $firstname {
        get => $this->firstname;
        set => $this->firstname = $value;
    }

    #[ORM\Column(type: 'phone_number', nullable: true)]
    public ?PhoneNumber $phone {
        get => $this->phone;
        set => $this->phone = $value;
    }

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    public ?string $city {
        get => $this->city;
        set => $this->city = $value;
    }

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    public ?string $postalCode {
        get => $this->postalCode;
        set => $this->postalCode = $value;
    }

    #[ORM\Column(type: Types::STRING, length: 3, nullable: true)]
    public ?string $country {
        get => $this->country;
        set => $this->country = $value;
    }
}
