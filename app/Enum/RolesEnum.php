<?php
namespace App\Enum;

enum RolesEnum: string
{
    case ADMIN = '1';
    case PARECERISTA = '2';
    case JULGADOR = '3';
    case INSTITUICAO = '4';
    case COMISSAO = '5';
    case AVALIADOR = '6';
}
