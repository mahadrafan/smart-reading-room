n   <?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    public function index(Request $request)
    {
        $notifications = UserNotification::where('user_id', Auth::id())
        $query = UserNotification::where('user_id', Auth::id())
            ->with('loan.book')
            ->orderByDesc('notification_id')
            ->get();
            ->orderByDesc('notification_id');

        // filter berdasarkan tab
        $tab = $request->tab ?? 'semua';
        if ($tab == 'peminjaman') {
            $query->whereIn('type', ['Pengajuan', 'Dipinjam', 'Dikembalikan', 'Gagal', 'Terlambat', 'Perpanjangan']);
        } elseif ($tab == 'pengumuman') {
            $query->where('type', 'Pengumuman');
        }

        $notifications = $query->get();

        return view('user.notifications', compact('notifications'));
    }
}
