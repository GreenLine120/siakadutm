<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }
    public function index()
    {
        if ($this->session->userdata('email')) {
            redirect('user');
        }
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email', [
            'required' => 'Harap Isi Email!',
            'valid_email' => 'Email Salah!'
        ]);
        $this->form_validation->set_rules('password', 'Password', 'trim|required', [
            'required' => 'Masukan Kata Sandi!'
        ]);
        if ($this->form_validation->run() == false) {
            $this->load->view('templates/auth_header');
            $this->load->view('auth/login');
            $this->load->view('templates/auth_footer');
        } else {
            $this->_login();
        }
    }
    private function _login()
    {
        $email = $this->input->post('email');
        $password = $this->input->post('password');
        $user = $this->db->get_where('user', ['email' => $email])->row_array();
        //jika usernya ada
        if ($user) {
            //jika usernya aktif
            if ($user['is_active'] == 1) {
                //cek password
                if (password_verify($password, $user['password'])) {
                    $data = [
                        'email' => $user['email'],
                        'role_id' => $user['role_id']
                    ];
                    $this->session->set_userdata($data);
                    if ($user['role_id'] == 1) {
                        redirect('admin');
                    } else {
                        redirect('user');
                    }
                } else {
                    $this->session->set_flashdata(
                        'massage',
                        '<div class="alert alert-danger" role="alert">Kata Sandi Salah</div>'
                    );
                    redirect('auth');
                }
            } else {
                $this->session->set_flashdata(
                    'massage',
                    '<div class="alert alert-danger" role="alert">Email Belum Teraktivasi</div>'
                );
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata(
                'massage',
                '<div class="alert alert-danger" role="alert">Email Belum Terdaftar</div>'
            );
            redirect('auth');
        }
    }
    public function registration()
    {
        if ($this->session->userdata('email')) {
            redirect('user');
        }
        $this->form_validation->set_rules(
            'name',
            'Name',
            'required|trim',
            [
                'required' => 'Nama Harus Diisi'
            ]
        );
        $this->form_validation->set_rules(
            'email',
            'Email',
            'required|trim|valid_email|is_unique[user.email]',
            [
                'is_unique' => 'Email Sudah Terdaftar',
                'valid_email' => 'Harus Diisi Dengan Email Yang Benar',
                'required' => 'Email Tidak Boleh Kosong'
            ]
        );
        $this->form_validation->set_rules(
            'password1',
            'Password1',
            'required|trim|min_length[6]|matches[password2]',
            [
                'required' => 'Kata Sandi Tidak Boleh Kosong',
                'matches' => 'Kata Sandi Tidak Sesuai!',
                'min_length' => 'Kata Sandi Terlalu Pendek!'
            ]
        );
        $this->form_validation->set_rules('password2', 'Password2', 'required|trim|matches[password1]');
        if ($this->form_validation->run() == false) {
            $this->load->view('templates/auth_header');
            $this->load->view('auth/registration');
            $this->load->view('templates/auth_footer');
        } else {
            $email = $this->input->post('email', true);
            $data = [
                'name' => htmlspecialchars($this->input->post('name', true)),
                'email' => htmlspecialchars($email),
                'image' => 'default.jpg',
                'password' => password_hash($this->input->post('password1'), PASSWORD_DEFAULT),
                'role_id' => 2,
                'is_active' => 0,
                'date_created' => time()
            ];

            $token = base64_encode(random_bytes(32));
            $user_token = [
                'email' => $email,
                'token' => $token,
                'date_created' => time()
            ];

            $this->db->insert('user', $data);
            $this->db->insert('user_token', $user_token);
            $this->_sendEmail($token, 'verify');

            $this->session->set_flashdata('massage', '<div class="alert alert-success" role="alert">Terimakasih, Akunmu Sudah Terdaftar. Silahkan Aktivasi</div>');
            redirect('auth');
        }
    }

    private function _sendEmail($token, $type)
    {
        $config = [
            'protocol' => 'smtp',
            'smtp_host' => 'ssl://smtp.googlemail.com',
            'smtp_user' => 'utmwebsite123@gmail.com',
            'smtp_pass' => 'utm123456789',
            'smtp_port' => 465,
            'mailtype' => 'html',
            'charset' => 'utf-8',
            'newline' => "\r\n"
        ];
        $this->email->initialize($config);

        $this->email->from('utmwebsite123@gmail.com', 'utm website');
        $this->email->to($this->input->post('email'));

        if ($type == 'verify') {
            $this->email->subject('Akun Verifikasi');
            $this->email->message('Klik link untuk mengverifikasi akun anda : 
            <a href="' . base_url() . 'auth/verify?email=' .
                $this->input->post('email') . '&token=' . urlencode($token) . '">Aktivasi</a>');
        } else if ($type == 'forgot') {
            $this->email->subject('Atur Ulang Kata Sandi');
            $this->email->message('Klik link untuk atur ulang kata sandi anda : 
            <a href="' . base_url() . 'auth/resetpassword?email=' .
                $this->input->post('email') . '&token=' . urlencode($token) . '">Atur ulang kata sandi</a>');
        }
        if ($this->email->send()) {
            return true;
        } else {
            echo $this->email->print_debugger();
            die;
        }
    }

    public function verify()
    {
        $email = $this->input->get('email');
        $token = $this->input->get('token');

        $user = $this->db->get_where('user', ['email' => $email])->row_array();

        if ($user) {
            $user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();
            if ($user_token) {
                if (time() - $user_token['date_created'] < (60 * 60 * 24)) {
                    $this->db->set('is_active', 1);
                    $this->db->where('email', $email);
                    $this->db->update('user');

                    $this->db->delete('user_token', ['email' => $email]);
                    $this->session->set_flashdata(
                        'massage',
                        '<div class="alert alert-success" role="alert">' . $email . ', Sudah Teraktivasi. Silahkan Login</div>'
                    );
                    redirect('auth');
                } else {
                    $this->db->delete('user', ['email' => $email]);
                    $this->db->delete('user_token', ['email' => $email]);
                    $this->session->set_flashdata(
                        'massage',
                        '<div class="alert alert-danger" role="alert">Aktivasi Gagal! Token Kadaluarsa</div>'
                    );
                    redirect('auth');
                }
            } else {
                $this->session->set_flashdata(
                    'massage',
                    '<div class="alert alert-danger" role="alert">Aktivasi Gagal! Token Salah</div>'
                );
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata(
                'massage',
                '<div class="alert alert-danger" role="alert">Aktivasi Gagal! Email Salah</div>'
            );
            redirect('auth');
        }
    }

    public function logout()
    {
        $this->session->unset_userdata('email');
        $this->session->unset_userdata('role_id');
        $this->session->set_flashdata('massage', '<div class="alert alert-success" role="alert">Kamu Sudah Keluar</div>');
        redirect('auth');
    }

    public function blocked()
    {
        $this->load->view('auth/blocked');
    }

    public function forgotPassword()
    {
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email', [
            'required' => 'Tidak Boleh Kosong',
            'valid_email' => 'Harus Diisi Dengan Email Yang Benar'
        ]);
        if ($this->form_validation->run() == false) {
            $data['title'] = 'Lupa Kata Sandi';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/forgot-password');
            $this->load->view('templates/auth_footer');
        } else {
            $email = $this->input->post('email');
            $user = $this->db->get_where('user', ['email' => $email, 'is_active' => 1])->row_array();

            if ($user) {
                $token = base64_encode(random_bytes(32));
                $user_token = [
                    'email' => $email,
                    'token' => $token,
                    'date_created' => time()
                ];

                $this->db->insert('user_token', $user_token);
                $this->_sendEmail($token, 'forgot');

                $this->session->set_flashdata('massage', '
                <div class="alert alert-success" role="alert">Silahkan cek email untuk mengatur ulang kata sandi</div>');
                redirect('auth/forgotpassword');
            } else {
                $this->session->set_flashdata('massage', '
                <div class="alert alert-danger" role="alert">Email Tidak Terdaftar atau Belum Teraktivasi</div>');
                redirect('auth/forgotpassword');
            }
        }
    }

    public function resetPassword()
    {
        $email = $this->input->get['email'];
        $token = $this->input->get['token'];

        $user = $this->db->get_where('user', ['email' => $email])->row_array();
        if ($user) {
            $user_token = $this->db->get_where('user_token', ['token' => $token])->row_array();
            if ($user_token) {
                if (time() - $user_token['date_created'] < (60 * 60 * 24)) {
                    $this->db->set('is_active', 1);
                    $this->session->set_userdata('reset_email', $email);
                    $this->changePassword();
                    $this->db->where('email', $email);
                    $this->db->update('user');
                    $this->db->delete('user_token', ['email' => $email]);
                    $this->session->set_flashdata(
                        'massage',
                        '<div class="alert alert-success" role="alert">' . $email . ', Sudah Teraktivasi. Silahkan Login</div>'
                    );
                    redirect('auth');
                }else {
                    $this->db->delete('user', ['email' => $email]);
                    $this->db->delete('user_token', ['email' => $email]);
                    $this->session->set_flashdata(
                        'massage',
                        '<div class="alert alert-danger" role="alert">Aktivasi Gagal! Token Kadaluarsa</div>'
                    );
                    redirect('auth');
                }
            } else {
                $this->session->set_flashdata('massage', '
                <div class="alert alert-danger" role="alert">Gagal atur ulang kata sandi! Token Salah</div>');
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata('massage', '
            <div class="alert alert-danger" role="alert">Gagal atur ulang kata sandi! Email Salah</div>');
            redirect('auth');
        }
    }

    public function changePassword()
    {
        if (!$this->session->userdata('reset_email')) {
            redirect('auth');
        }
        $this->form_validation->set_rules('password1', 'Password', 'required|trim|min_length[6]|matches[password2]', [
            'required' => 'Kata Sandi Tidak Boleh Kosong',
            'matches' => 'Kata Sandi Tidak Sesuai!',
            'min_length' => 'Kata Sandi Terlalu Pendek!'
        ]);
        $this->form_validation->set_rules('password2', 'Repreat Password', 'required|trim|min_length[6]|matches[password1]', [
            'required' => 'Kata Sandi Tidak Boleh Kosong',
            'matches' => 'Kata Sandi Tidak Sesuai!',
            'min_length' => 'Kata Sandi Terlalu Pendek!'
        ]);

        if ($this->form_validation->run() == false) {
            $data['title'] = 'Lupa Kata Sandi';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/change-password');
            $this->load->view('templates/auth_footer');
        } else {
            $password = password_hash($this->input->post('password1'), PASSWORD_DEFAULT);
            $email = $this->session->userdata('reset_email');
            $this->db->set('password', $password);
            $this->db->where('email', $email);
            $this->db->update('user');
            $this->session->unset_userdata('reset_email');

            $this->session->set_flashdata('massage', '
            <div class="alert alert-success" role="alert">Kata Sandi berhasil diubah! Silahkan Login</div>');
            redirect('auth');
        }
    }
}
