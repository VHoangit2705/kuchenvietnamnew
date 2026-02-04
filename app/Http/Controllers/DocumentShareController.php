<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\KyThuat\DocumentShare;
use App\Models\KyThuat\DocumentVersion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class DocumentShareController extends Controller
{
    // --- ADMIN METHODS ---

    /**
     * Lấy danh sách link chia sẻ của một version
     */
    public function index(Request $request, $document_version_id)
    {
        $shares = DocumentShare::where('document_version_id', $document_version_id)
            ->where('status', '!=', 'revoked') // Hoặc show cả revoked nếu muốn
            ->orderByDesc('created_at')
            ->get();

        // Format lại data để hiển thị dễ dàng
        $data = $shares->map(function ($share) {
            return [
                'id'            => $share->id,
                'share_token'   => $share->share_token,
                'full_url' => route('docs.share.show', $share->share_token),
                'permission'    => $share->permission,
                'has_password'  => !empty($share->password_hash),
                'expires_at'    => $share->expires_at ? $share->expires_at->format('Y-m-d H:i') : 'Vĩnh viễn',
                'access_count'  => $share->access_count,
                'status'        => $share->status,
                'is_expired'    => $share->expires_at && $share->expires_at->isPast(),
            ];
        });

        return response()->json($data);
    }

    /**
     * Tạo link chia sẻ mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'document_version_id' => 'required|integer',
            'permission'          => 'required|in:view,download',
            'expires_at'          => 'nullable|date',
            'password'            => 'nullable|string|min:4',
        ]);

        $version = DocumentVersion::findOrFail($request->document_version_id);

        $share = DocumentShare::create([
            'document_version_id' => $version->id,
            'share_token'         => Str::uuid()->toString(),
            'permission'          => $request->permission,
            'password_hash'       => $request->filled('password') ? Hash::make($request->password) : null,
            'expires_at'          => $request->expires_at ? Carbon::parse($request->expires_at) : null,
            'created_by'          => Auth::id(),
            'status'              => 'active',
        ]);

        return response()->json([
            'message'   => 'Đã tạo link chia sẻ.',
            'share_url' => route('document.share.public_show', $share->share_token),
        ]);
    }

    /**
     * Thu hồi link
     */
    public function revoke($id)
    {
        $share = DocumentShare::findOrFail($id);
        $share->update(['status' => 'revoked']);
        return response()->json(['message' => 'Đã thu hồi link chia sẻ.']);
    }

    // --- PUBLIC METHODS ---

    /**
     * Trang xem tài liệu công khai
     */
    public function publicShow($token)
    {
        $share = DocumentShare::where('share_token', $token)->first();

        // 1. Check tồn tại & trạng thái
        if (!$share || $share->status !== 'active') {
            abort(404, 'Link chia sẻ không tồn tại hoặc đã bị thu hồi.');
        }

        // 2. Check hạn
        if ($share->expires_at && $share->expires_at->isPast()) {
            abort(403, 'Link chia sẻ đã hết hạn.');
        }

        // 3. Logic Password
        if ($share->password_hash) {
            $sessionKey = 'doc_share_auth_' . $token;
            if (!Session::has($sessionKey)) {
                return view('technicaldocument.share.private-auth', compact('token'));
            }
        }

        // Update stats (có thể dùng cache để tránh spam update DB)
        $share->increment('access_count');
        $share->update(['last_access_at' => now()]);

        $version = $share->documentVersion;
        $document = $version->technicalDocument;

        // Chu xử lý hiển thị file
        $storageUrl = rtrim(asset('storage'), '/');
        $fileUrl = asset('storage/' . ltrim($version->file_path, '/'));

        return view('technicaldocument.share.public-view', compact('share', 'document', 'version', 'fileUrl'));
    }

    /**
     * Xử lý nhập mật khẩu
     */
    public function publicAuth(Request $request, $token)
    {
        $share = DocumentShare::where('share_token', $token)->firstOrFail();

        if (Hash::check($request->password, $share->password_hash)) {
            $sessionKey = 'doc_share_auth_' . $token;
            Session::put($sessionKey, true);
            return redirect()->route('document.share.public_show', $token);
        }

        return back()->withErrors(['password' => 'Mật khẩu không đúng.']);
    }

    /**
     * Download file (nếu có quyền)
     */
    public function download($token)
    {
        $share = DocumentShare::where('share_token', $token)->firstOrFail();

        // Re-check security checks 
        if ($share->status !== 'active' || ($share->expires_at && $share->expires_at->isPast())) {
            abort(403, 'Link không khả dụng.');
        }

        if ($share->password_hash) {
            $sessionKey = 'doc_share_auth_' . $token;
            if (!Session::has($sessionKey)) {
                abort(403, 'Chưa xác thực mật khẩu.');
            }
        }

        // Check permission
        if ($share->permission !== 'download') {
            abort(403, 'Link này chỉ cho phép xem, không cho phép tải.');
        }

        $version = $share->documentVersion;
        $fileUrl = asset('storage/' . ltrim($version->file_path, '/'));


        if (!file_exists($fullPath)) {
            abort(404, 'File gốc không tìm thấy.');
        }

        return response()->download($fullPath, basename($version->file_path));
    }
}
