<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

try {
    if ($method === 'GET' && $path === '/health') {
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'ok';
        exit;
    }

    if ($method === 'GET' && $path === '/dev/mailbox') {
        if (env_value('APP_ENV', 'local') === 'production' || Mailer::mode() !== 'log') {
            http_response_code(404);
            render('errors/404', ['title' => 'Không tìm thấy trang']);
            exit;
        }
        render('dev/mailbox', ['title' => 'Hộp thư kiểm thử local', 'messages' => Mailer::loggedMessages()]);
        exit;
    }

    if ($method === 'GET' && preg_match('#^/share/([a-f0-9]{64})$#', $path, $matches)) {
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        $resume = ResumeShareRepository::findPublicByToken((string) $matches[1]);
        if ($resume === null) {
            http_response_code(404);
            render('errors/404', ['title' => 'Link chia sẻ không còn hiệu lực']);
            exit;
        }
        $sections = decode_sections((string) $resume['sections_json']);
        require dirname(__DIR__) . '/views/resume/public-preview.php';
        exit;
    }

    if ($method === 'GET' && $path === '/') {
        if (Auth::check()) {
            redirect(Auth::afterLoginPath());
        }
        render('home', ['title' => 'Tạo CV rõ ràng và chuyên nghiệp']);
        exit;
    }

    if ($method === 'GET' && $path === '/register') {
        if (Auth::check()) {
            redirect(Auth::afterLoginPath());
        }
        render('auth/register', ['title' => 'Đăng ký']);
        exit;
    }

    if ($method === 'POST' && $path === '/register') {
        Csrf::verifyRequest();
        if (Auth::check()) {
            redirect(Auth::afterLoginPath());
        }

        $input = [
            'name' => (string) ($_POST['name'] ?? ''),
            'email' => (string) ($_POST['email'] ?? ''),
        ];
        set_old_input($input);
        $errors = Auth::register($input['name'], $input['email'], (string) ($_POST['password'] ?? ''));
        if ($errors !== []) {
            render('auth/register', ['title' => 'Đăng ký', 'errors' => $errors]);
            exit;
        }

        clear_old_input();
        if (!Auth::emailVerificationRequired()) {
            flash('success', 'Tài khoản đã được tạo. Bạn có thể bắt đầu làm CV.');
        }
        redirect(Auth::afterLoginPath());
    }

    if ($method === 'GET' && $path === '/login') {
        if (Auth::check()) {
            redirect(Auth::afterLoginPath());
        }
        render('auth/login', ['title' => 'Đăng nhập']);
        exit;
    }

    if ($method === 'POST' && $path === '/login') {
        Csrf::verifyRequest();
        if (Auth::attempt((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''))) {
            flash('success', 'Đăng nhập thành công.');
            redirect(Auth::afterLoginPath());
        }
        render('auth/login', ['title' => 'Đăng nhập', 'errors' => [Auth::lastLoginError()]]);
        exit;
    }

    if ($method === 'POST' && $path === '/logout') {
        Csrf::verifyRequest();
        Auth::logout();
        redirect('/');
    }

    if ($method === 'GET' && $path === '/forgot-password') {
        if (Auth::check()) {
            redirect(Auth::afterLoginPath());
        }
        render('auth/forgot-password', ['title' => 'Quên mật khẩu']);
        exit;
    }

    if ($method === 'POST' && $path === '/forgot-password') {
        Csrf::verifyRequest();
        Auth::requestPasswordReset((string) ($_POST['email'] ?? ''));
        render('auth/forgot-password', [
            'title' => 'Quên mật khẩu',
            'submitted' => true,
        ]);
        exit;
    }

    if ($method === 'GET' && preg_match('#^/reset-password/([a-f0-9]{64})$#', $path, $matches)) {
        header('Referrer-Policy: no-referrer');
        $token = (string) $matches[1];
        if (!AccountTokenRepository::isResetTokenValid($token)) {
            render('auth/reset-password', [
                'title' => 'Đặt lại mật khẩu',
                'invalidToken' => true,
            ]);
            exit;
        }
        render('auth/reset-password', ['title' => 'Đặt lại mật khẩu', 'token' => $token]);
        exit;
    }

    if ($method === 'POST' && $path === '/reset-password') {
        header('Referrer-Policy: no-referrer');
        Csrf::verifyRequest();
        $token = (string) ($_POST['token'] ?? '');
        $errors = Auth::resetPassword(
            $token,
            (string) ($_POST['password'] ?? ''),
            (string) ($_POST['password_confirmation'] ?? '')
        );
        if ($errors !== []) {
            render('auth/reset-password', [
                'title' => 'Đặt lại mật khẩu',
                'token' => $token,
                'invalidToken' => !AccountTokenRepository::isResetTokenValid($token),
                'errors' => $errors,
            ]);
            exit;
        }
        flash('success', 'Đã đặt lại mật khẩu. Hãy đăng nhập bằng mật khẩu mới.');
        redirect('/login');
    }

    if ($method === 'GET' && $path === '/verify-email') {
        $user = Auth::requireUser();
        if (Auth::isVerified($user)) {
            flash('success', 'Email của bạn đã được xác minh.');
            redirect('/dashboard');
        }
        render('auth/verify-email', ['title' => 'Xác minh email', 'user' => $user]);
        exit;
    }

    if ($method === 'POST' && $path === '/verify-email/resend') {
        Csrf::verifyRequest();
        $user = Auth::requireUser();
        $errors = Auth::requestVerificationEmail((int) $user['id']);
        if ($errors !== []) {
            render('auth/verify-email', ['title' => 'Xác minh email', 'user' => $user, 'errors' => $errors]);
            exit;
        }
        flash('success', 'Đã gửi lại email xác minh. Hãy kiểm tra hộp thư đến và thư rác.');
        redirect('/verify-email');
    }

    if ($method === 'GET' && preg_match('#^/verify-email/confirm/([a-f0-9]{64})$#', $path, $matches)) {
        header('Referrer-Policy: no-referrer');
        if (!Auth::verifyEmail((string) $matches[1])) {
            flash('error', 'Link xác minh không hợp lệ hoặc đã hết hạn.');
            redirect(Auth::check() ? '/verify-email' : '/login');
        }
        flash('success', 'Email đã được xác minh thành công.');
        redirect(Auth::check() ? '/dashboard' : '/login');
    }

    if ($method === 'GET' && $path === '/dashboard') {
        $user = Auth::requireVerifiedUser();
        $resumes = ResumeRepository::allForUser((int) $user['id']);
        render('dashboard', ['title' => 'CV của tôi', 'user' => $user, 'resumes' => $resumes]);
        exit;
    }

    if ($method === 'GET' && $path === '/account') {
        $user = Auth::requireUser();
        render('account', [
            'title' => 'Tài khoản',
            'user' => $user,
            'activities' => ActivityLogger::recentForUser((int) $user['id']),
        ]);
        exit;
    }

    if ($method === 'POST' && $path === '/account/profile') {
        Csrf::verifyRequest();
        $user = Auth::requireUser();
        $errors = AccountRepository::updateProfile((int) $user['id'], (string) ($_POST['name'] ?? ''));
        if ($errors !== []) {
            render('account', [
                'title' => 'Tài khoản',
                'user' => $user,
                'activities' => ActivityLogger::recentForUser((int) $user['id']),
                'profileErrors' => $errors,
            ]);
            exit;
        }
        Auth::refreshCachedUser();
        flash('success', 'Đã cập nhật hồ sơ tài khoản.');
        redirect('/account');
    }

    if ($method === 'POST' && $path === '/account/password') {
        Csrf::verifyRequest();
        $user = Auth::requireUser();
        $errors = Auth::changePassword(
            (int) $user['id'],
            (string) ($_POST['current_password'] ?? ''),
            (string) ($_POST['new_password'] ?? ''),
            (string) ($_POST['new_password_confirmation'] ?? '')
        );
        if ($errors !== []) {
            render('account', [
                'title' => 'Tài khoản',
                'user' => $user,
                'activities' => ActivityLogger::recentForUser((int) $user['id']),
                'passwordErrors' => $errors,
            ]);
            exit;
        }
        flash('success', 'Đã đổi mật khẩu.');
        redirect('/account');
    }

    if ($method === 'GET' && $path === '/account/export') {
        $user = Auth::requireUser();
        $payload = AccountRepository::exportPersonalData((int) $user['id']);
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="cloudcv-personal-data-' . gmdate('Y-m-d') . '.json"');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'POST' && $path === '/account/delete') {
        Csrf::verifyRequest();
        $user = Auth::requireUser();
        $errors = AccountRepository::deleteAccount(
            (int) $user['id'],
            (string) ($_POST['password'] ?? ''),
            (string) ($_POST['confirmation'] ?? '')
        );
        if ($errors !== []) {
            render('account', [
                'title' => 'Tài khoản',
                'user' => $user,
                'activities' => ActivityLogger::recentForUser((int) $user['id']),
                'deleteErrors' => $errors,
            ]);
            exit;
        }
        Auth::clearSession();
        redirect('/?account_deleted=1');
    }

    if ($method === 'POST' && $path === '/resume/create') {
        Csrf::verifyRequest();
        $user = Auth::requireVerifiedUser();
        $resumeId = ResumeRepository::create((int) $user['id'], (string) ($_POST['title'] ?? 'CV mới'));
        flash('success', 'Đã tạo CV mới. Hãy bổ sung thông tin của bạn.');
        redirect('/resume/edit?id=' . $resumeId);
    }

    if ($method === 'GET' && $path === '/resume/edit') {
        $user = Auth::requireVerifiedUser();
        $resume = ResumeRepository::requireOwned((int) ($_GET['id'] ?? 0), (int) $user['id']);
        render('resume/editor', [
            'title' => 'Chỉnh sửa CV',
            'user' => $user,
            'resume' => $resume,
            'sections' => decode_sections((string) $resume['sections_json']),
        ]);
        exit;
    }

    if ($method === 'POST' && $path === '/resume/save') {
        Csrf::verifyRequest();
        $user = Auth::requireVerifiedUser();
        $resumeId = (int) ($_POST['id'] ?? 0);
        ResumeRepository::update($resumeId, (int) $user['id'], $_POST);
        flash('success', 'Đã lưu CV.');
        redirect('/resume/edit?id=' . $resumeId);
    }

    if ($method === 'POST' && $path === '/resume/autosave') {
        Csrf::verifyRequest();
        $user = Auth::user();
        if ($user === null) {
            json_response(['ok' => false, 'message' => 'Phiên đăng nhập đã hết hạn.'], 401);
        }
        if (Auth::emailVerificationRequired() && !Auth::isVerified($user)) {
            json_response(['ok' => false, 'message' => 'Hãy xác minh email trước khi chỉnh sửa CV.'], 403);
        }
        $resumeId = (int) ($_POST['id'] ?? 0);
        ResumeRepository::update($resumeId, (int) $user['id'], $_POST, false);
        json_response(['ok' => true, 'saved_at' => gmdate('c')]);
    }

    if ($method === 'POST' && $path === '/resume/delete') {
        Csrf::verifyRequest();
        $user = Auth::requireVerifiedUser();
        ResumeRepository::delete((int) ($_POST['id'] ?? 0), (int) $user['id']);
        flash('success', 'Đã xóa CV.');
        redirect('/dashboard');
    }

    if ($method === 'POST' && $path === '/resume/clone') {
        Csrf::verifyRequest();
        $user = Auth::requireVerifiedUser();
        $resumeId = ResumeRepository::cloneResume((int) ($_POST['id'] ?? 0), (int) $user['id']);
        flash('success', 'Đã tạo một bản sao để bạn tùy chỉnh theo vị trí khác.');
        redirect('/resume/edit?id=' . $resumeId);
    }

    if ($method === 'GET' && $path === '/resume/preview') {
        $user = Auth::requireVerifiedUser();
        $resume = ResumeRepository::requireOwned((int) ($_GET['id'] ?? 0), (int) $user['id']);
        $sections = decode_sections((string) $resume['sections_json']);
        require dirname(__DIR__) . '/views/resume/preview.php';
        exit;
    }

    if ($method === 'GET' && $path === '/resume/share') {
        $user = Auth::requireVerifiedUser();
        $resumeId = (int) ($_GET['id'] ?? 0);
        $resume = ResumeRepository::requireOwned($resumeId, (int) $user['id']);
        $share = ResumeShareRepository::activeForResume($resumeId, (int) $user['id']);
        $generatedUrl = (string) ($_SESSION['_generated_share_url'] ?? '');
        unset($_SESSION['_generated_share_url']);
        render('resume/share', [
            'title' => 'Chia sẻ CV',
            'user' => $user,
            'resume' => $resume,
            'share' => $share,
            'generatedUrl' => $generatedUrl,
        ]);
        exit;
    }

    if ($method === 'POST' && $path === '/resume/share/create') {
        Csrf::verifyRequest();
        $user = Auth::requireVerifiedUser();
        $resumeId = (int) ($_POST['id'] ?? 0);
        $_SESSION['_generated_share_url'] = ResumeShareRepository::create($resumeId, (int) $user['id'], (int) ($_POST['valid_days'] ?? 30));
        flash('success', 'Đã tạo link chia sẻ mới. Hãy sao chép link trước khi rời trang.');
        redirect('/resume/share?id=' . $resumeId);
    }

    if ($method === 'POST' && $path === '/resume/share/revoke') {
        Csrf::verifyRequest();
        $user = Auth::requireVerifiedUser();
        $resumeId = (int) ($_POST['id'] ?? 0);
        ResumeShareRepository::revoke($resumeId, (int) $user['id']);
        flash('success', 'Đã thu hồi link chia sẻ.');
        redirect('/resume/share?id=' . $resumeId);
    }

    if ($method === 'GET' && $path === '/resume/export') {
        $user = Auth::requireVerifiedUser();
        $payload = ResumeRepository::exportData((int) ($_GET['id'] ?? 0), (int) $user['id']);
        $filename = slug_filename((string) ($payload['resume']['title'] ?? 'cv')) . '-' . gmdate('Y-m-d') . '.json';
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($method === 'POST' && $path === '/resume/import') {
        Csrf::verifyRequest();
        $user = Auth::requireVerifiedUser();
        $file = $_FILES['resume_json'] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Hãy chọn một file JSON đã xuất từ CloudCV Builder.');
            redirect('/dashboard');
        }
        if ((int) ($file['size'] ?? 0) <= 0 || (int) $file['size'] > 524288) {
            flash('error', 'File JSON không hợp lệ hoặc vượt quá giới hạn 512 KB.');
            redirect('/dashboard');
        }
        if (strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'json') {
            flash('error', 'Chỉ chấp nhận file có phần mở rộng .json.');
            redirect('/dashboard');
        }

        $contents = file_get_contents((string) $file['tmp_name']);
        if ($contents === false) {
            flash('error', 'Không thể đọc file JSON.');
            redirect('/dashboard');
        }
        try {
            $payload = json_decode($contents, true, 128, JSON_THROW_ON_ERROR);
            if (!is_array($payload)) {
                throw new InvalidArgumentException('File JSON không hợp lệ.');
            }
            $resumeId = ResumeRepository::importData((int) $user['id'], $payload);
            flash('success', 'Đã nhập một bản CV mới từ file JSON.');
            redirect('/resume/edit?id=' . $resumeId);
        } catch (JsonException | InvalidArgumentException $exception) {
            flash('error', $exception->getMessage());
            redirect('/dashboard');
        }
    }

    http_response_code(404);
    render('errors/404', ['title' => 'Không tìm thấy trang']);
} catch (Throwable $exception) {
    http_response_code(500);
    $isLocal = env_value('APP_ENV', 'local') !== 'production';
    render('errors/500', [
        'title' => 'Có lỗi xảy ra',
        'message' => $isLocal ? $exception->getMessage() : 'Hệ thống chưa thể xử lý yêu cầu. Hãy thử lại sau.',
    ]);
}
