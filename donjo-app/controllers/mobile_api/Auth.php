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


    private function authtoken()
    {
        $headers = $this->input->request_headers();
        $token = null;

        if (isset($headers['Authorization'])) {
            $matches = array();
            preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches);
            if (isset($matches[1])) {
                $token = $matches[1];
            }
        }

        if (!$token) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Token tidak ditemukan'
                ]))->_display();
            exit;
        }

        /** @var User|null $user */
        $user = User::where('token', $token)->first();

        if (!$user) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Token tidak valid'
                ]))->_display();
            exit;
        }

        return $user;
    }

    public function logout()
    {
        $user = $this->authtoken();

        $user->update(['token' => null]);

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode([
                'success' => true,
                'message' => 'Logout berhasil'
            ]));
    }

    public function ubah_profil()
    {
        $user = $this->authtoken();

        $password_lama = $this->input->post('password_lama');
        $username_baru = $this->input->post('username_baru');
        $password_baru = $this->input->post('password_baru');

        if (empty($password_lama)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Password lama harus diisi'
                ]));
        }

        if (!password_verify($password_lama, $user->password)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Password lama salah'
                ]));
        }

        $updateData = [];

        if (!empty($username_baru)) {
            $updateData['username'] = $username_baru;
        }

        if (!empty($password_baru)) {
            if (strlen($password_baru) < 6) {
                 return $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(400)
                    ->set_output(json_encode([
                        'success' => false,
                        'message' => 'Password baru minimal 6 karakter'
                    ]));
            }
            $updateData['password'] = password_hash($password_baru, PASSWORD_BCRYPT);
        }

        if (empty($updateData)) {
             return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode([
                    'success' => true,
                    'message' => 'Tidak ada perubahan data'
                ]));
        }

        $user->update($updateData);

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode([
                'success' => true,
                'message' => 'Profil berhasil diubah'
            ]));
    }
}
