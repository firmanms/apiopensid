<?php

defined('BASEPATH') || exit('No direct script access allowed');

use App\Models\User;

class Auth extends Api_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('password'); // Load password helper for checking hash
    }

    public function login()
    {
        $username = $this->input->post('username');
        $password = $this->input->post('password');

        if (empty($username) || empty($password)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Username dan Password harus diisi'
                ]));
        }

        /** @var User|null $user */
        $user = User::where('username', $username)->first();

        if ($user && password_verify($password, $user->password)) {
            // Generate token sederhana (untuk production sebaiknya gunakan JWT atau Sanctum)
            $token = bin2hex(random_bytes(32));
            
            // Simpan token ke database field 'token'
            $user->update([
                'token' => $token,
                // 'last_login' => date('Y-m-d H:i:s') // Opsional: update last_login
            ]);

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode([
                    'success' => true,
                    'message' => 'Login berhasil',
                    'data' => [
                        'token' => $token,
                        'user' => [
                            'id' => $user->id,
                            'username' => $user->username,
                            'nama' => $user->nama,
                            'foto' => $user->foto
                        ]
                    ]
                ]));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(401)
            ->set_output(json_encode([
                'success' => false,
                'message' => 'Username atau Password salah'
            ]));
    }
}
