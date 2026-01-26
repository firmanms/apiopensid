<?php

defined('BASEPATH') || exit('No direct script access allowed');

use App\Models\Config;

class Profil extends Api_Controller
{
    public function index()
    {
        // Verifikasi Token
        $headers = $this->input->request_headers();
        $token = null;

        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        if (!$token) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Token tidak ditemukan', // Token not found
                ]));
        }

        $user = \App\Models\User::where('token', $token)->first();

        if (!$user) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode([
                    'success' => false,
                    'message' => 'Token tidak valid', // Invalid token
                ]));
        }

        // Ambil data konfigurasi desa
        $config = Config::first();

        if ($config) {
            // Tambahkan URL lengkap untuk logo dan kantor desa jika perlu
            $data = $config->toArray();
            $data['logo_url'] = base_url($config->path_logo);
            $data['kantor_desa_url'] = base_url($config->path_kantor_desa);

            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode([
                    'success' => true,
                    'data' => $data
                ]));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_status_header(404)
            ->set_output(json_encode([
                'success' => false,
                'message' => 'Data profil desa tidak ditemukan'
            ]));
    }
}
