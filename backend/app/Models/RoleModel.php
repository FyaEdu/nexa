<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table         = 'roles';
    protected $returnType    = 'array';
    protected $allowedFields = ['code', 'name'];
}
