<?php

namespace App\Http\Resources\Manage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetailUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $level = $this->level ?: 'user';

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'photo' => $this->photo,

            // ✅ IMPORTANT: frontend uses this to show User/Admin
            'level' => $level,

            // ✅ Optional helper
            'is_admin' => $level === 'admin',

            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'department_id' => $this->department_id,
            'department' => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
                'code' => $this->department->code,
            ] : null,
        ];
    }
}
