<?php

namespace App\DTO;

use DateTimeInterface;

class UserTableDTO
{
    public $id;
    public $username;
    public $managerId;
    public $managerDisplayName;
    public $loginTime;
    public $role;

    public function __construct(array $row)
    {
        $loginTime = $row['login_time'];
        $this->id = $row['id'];
        $this->username = $row['username'] ?? null;
        $this->managerId = $row['manager_id'] ?? null;
        $this->managerDisplayName = $row['display_name'] ?? null;
        $this->loginTime = $loginTime instanceof DateTimeInterface ?  $loginTime->format('Y-m-d H:i:s') : null;
        $this->role = $row['role'] ?? null;
    }

    public function toArray(): array
    {
        return [
            "DT_RowId" => "row_" . $this->id,
            "DT_RowData" => [
                "pkey" => $this->id
            ],
            'id' => $this->id,
            'username' => $this->username,
            'manager_id' => $this->managerId,
            'manager_name' => $this->managerDisplayName,
            'login_time' => $this->loginTime,
            'role' => $this->role
        ];
    }
}
