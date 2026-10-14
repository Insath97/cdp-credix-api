<?php

namespace App\Enums;

enum PropertyType: string
{
    case House = 'house';
    case Land = 'land';
    case Apartment = 'apartment';
    case CommercialProperty = 'commercial_property';
    case Building = 'building';

    public function label(): string
    {
        return match ($this) {
            self::House              => 'House',
            self::Land               => 'Land',
            self::Apartment          => 'Apartment',
            self::CommercialProperty => 'Commercial Property',
            self::Building           => 'Building',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::House->value              => self::House->label(),
            self::Land->value               => self::Land->label(),
            self::Apartment->value          => self::Apartment->label(),
            self::CommercialProperty->value => self::CommercialProperty->label(),
            self::Building->value           => self::Building->label(),
        ];
    }
}
