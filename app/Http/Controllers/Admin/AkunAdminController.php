<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

// Kelola Admin: admin yang sedang login dapat melihat, menambah, dan mengubah akun admin lain (juga akunnya sendiri).
// Form daftar publik selalu membuat Peminjam, jadi akun admin baru hanya bisa dibuat dari sini.
// Akun admin tidak bisa dihapus: namanya tercatat di admin_logs, persetujuan (approved_by), pelunasan denda
// (fine_paid_by), dan data buku (created_by/updated_by), sehingga riwayat harus tetap jelas siapa pelakunya.
class AkunAdminController extends Controller
{
    const PER_HALAMAN = 10;

    // READ: daftar admin + pencarian NIP/ID atau nama, beserta jumlah aktivitas dan waktu aktivitas terakhir
    public function index(Request $request)
    {
        $cari = trim((string) $request->q);

        $admin = User::where('role', 'Admin')
            ->when($cari !== '', function ($q) use ($cari) {
                $q->where(function ($w) use ($cari) {
                    $w->where('nim_nip', 'like', '%' . $cari . '%')
                        ->orWhere('name', 'like', '%' . $cari . '%')
                        ->orWhere('email', 'like', '%' . $cari . '%');
                });
            })
            ->withCount('adminLogs as jumlah_aktivitas')
            ->withMax('adminLogs as aktivitas_terakhir', 'created_at')
            ->orderBy('name')
            ->paginate(self::PER_HALAMAN)
            ->withQueryString();

        if ($admin->isEmpty() && $admin->currentPage() > 1) {
            return redirect()->to($admin->url($admin->lastPage()));
        }

        return view('admin.akun-admin.index', [
            'admin'       => $admin,
            'cari'        => $cari,
            'jumlahTotal' => User::where('role', 'Admin')->count(),
        ]);
    }

    public function create()
    {
        return view('admin.akun-admin.create');
    }

    // CREATE: akun admin baru (role selalu Admin, tanpa jenis peminjam / kelas / jabatan)
    public function store(Request $request)
    {
        [$kodeTelepon, $nomorTelepon] = $this->rapikanTelepon($request);

        $request->validate($this->aturanData($kodeTelepon) + [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], $this->pesan(), $this->namaIsian());

        $akun = User::create([
            'role'     => 'Admin',
            'nim_nip'  => $request->nim_nip,
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $this->teleponTersimpan($kodeTelepon, $nomorTelepon),
            'password' => Hash::make($request->password),
        ]);

        AdminLog::catat('Tambah', 'users', $akun->user_id,
            'Menambahkan akun admin "' . $akun->name . '" (' . $akun->nim_nip . ')');

        return redirect()->route('admin.akun.index')
            ->with('status', 'Akun admin "' . $akun->name . '" berhasil dibuat. Admin baru dapat login dengan NIP/ID ' . $akun->nim_nip . '.');
    }

    public function edit($id)
    {
        return view('admin.akun-admin.edit', ['akun' => $this->cariAdmin($id)]);
    }

    // UPDATE: data diri admin (NIP/ID, nama, email, no. telepon)
    public function update(Request $request, $id)
    {
        $akun = $this->cariAdmin($id);
        [$kodeTelepon, $nomorTelepon] = $this->rapikanTelepon($request);

        $data = $request->validate($this->aturanData($kodeTelepon, $akun), $this->pesan(), $this->namaIsian());

        unset($data['phone_code']);
        $data['phone'] = $this->teleponTersimpan($kodeTelepon, $nomorTelepon);
        $akun->update($data);

        AdminLog::catat('Edit', 'users', $akun->user_id,
            'Mengubah data akun admin "' . $akun->name . '" (' . $akun->nim_nip . ')');

        return redirect()->route('admin.akun.edit', $akun->user_id)
            ->with('status', 'Data akun admin "' . $akun->name . '" berhasil diperbarui.');
    }

    // UPDATE: ganti password. Untuk akun sendiri wajib password lama; untuk admin lain cukup password baru (reset)
    public function password(Request $request, $id)
    {
        $akun = $this->cariAdmin($id);
        $akunSendiri = (int) $akun->user_id === (int) Auth::id();

        $request->validateWithBag('password', [
            'password_lama' => $akunSendiri ? ['required'] : ['nullable'],
            'password_baru' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password_lama.required'  => 'Password lama wajib diisi.',
            'password_baru.required'  => 'Password baru wajib diisi.',
            'password_baru.min'       => 'Password baru minimal 8 karakter.',
            'password_baru.confirmed' => 'Password baru dan ulangi password baru harus sama.',
        ]);

        if ($akunSendiri && ! Hash::check($request->password_lama, $akun->password)) {
            return back()->withErrors(['password_lama' => 'Password lama tidak sesuai.'], 'password');
        }

        $akun->update(['password' => Hash::make($request->password_baru)]);

        AdminLog::catat($akunSendiri ? 'Ganti Password' : 'Reset Password', 'users', $akun->user_id,
            ($akunSendiri ? 'Mengganti password akun sendiri' : 'Mereset password akun admin "' . $akun->name . '"')
                . ' (' . $akun->nim_nip . ')');

        return redirect()->route('admin.akun.edit', $akun->user_id)
            ->with('status', $akunSendiri
                ? 'Password akun Anda berhasil diganti.'
                : 'Password akun admin "' . $akun->name . '" berhasil direset. Beri tahu password baru kepada yang bersangkutan.');
    }

    // hanya akun ber-role Admin yang bisa dibuka dari menu ini (akun peminjam tidak bisa diubah lewat sini)
    private function cariAdmin($id): User
    {
        return User::where('role', 'Admin')->findOrFail($id);
    }

    // no. telepon + kode negara dirapikan seperti di form daftar & profil
    private function rapikanTelepon(Request $request): array
    {
        [$kode, $nomor] = User::rapikanTelepon($request->input('phone_code', User::KODE_INDONESIA), $request->phone);
        $request->merge(['phone_code' => $kode, 'phone' => $nomor]);

        return [$kode, $nomor];
    }

    private function aturanData(string $kodeTelepon, ?User $akun = null): array
    {
        $unik = fn (string $kolom) => Rule::unique('users', $kolom)->ignore($akun?->user_id, 'user_id');

        // no. telepon admin opsional (akun admin lama belum punya); bila diisi, aturannya sama dengan peminjam
        $telepon = request()->filled('phone')
            ? User::aturanTelepon($kodeTelepon)
            : ['phone_code' => ['nullable'], 'phone' => ['nullable']];

        return [
            'nim_nip' => ['required', 'string', 'max:20', $unik('nim_nip')],
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:100', $unik('email')],
        ] + $telepon;
    }

    private function teleponTersimpan(string $kode, string $nomor): ?string
    {
        return $nomor === '' ? null : User::gabungTelepon($kode, $nomor);
    }

    private function pesan(): array
    {
        return User::pesanTelepon() + [
            'required'  => ':attribute wajib diisi.',
            'email'     => 'Format email belum benar.',
            'max'       => ':attribute maksimal :max karakter.',
            'unique'    => ':attribute sudah dipakai akun lain.',
            'min'       => ':attribute minimal :min karakter.',
            'confirmed' => 'Password dan ulangi password harus sama.',
        ];
    }

    private function namaIsian(): array
    {
        return [
            'nim_nip'  => 'NIP / ID admin',
            'name'     => 'Nama lengkap',
            'email'    => 'Email',
            'phone'    => 'No. telepon',
            'password' => 'Password',
        ];
    }
}
