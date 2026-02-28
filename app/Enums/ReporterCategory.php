<?php

namespace App\Enums;

enum ReporterCategory: string
{
    case GeneralPublic = 'general_public';
    case PublicServant = 'public_servant';
    case FieldOfficer = 'field_officer';
}
