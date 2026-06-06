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

    if ($method === 'GET' && $path === '/') {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        render('home', ['title' => 'Tạo CV rõ ràng và chuyên nghiệp']);
        exit;
    }

    if ($method === 'GET' && $path === '/register') {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        render('auth/register', ['title' => 'Đăng ký']);
        exit;
    }

    if ($method === 'POST' && $path === '/register') {
        Csrf::verifyRequest();
        if (Auth::check()) {
            redirect('/dashboard');
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
        flash('success', 'Tài khoản đã được tạo. Bạn có thể bắt đầu làm CV.');
        redirect('/dashboard');
    }

    if ($method === 'GET' && $path === '/login') {
        if (Auth::check()) {
            redirect('/dashboard');
        }
        render('auth/login', ['title' => 'Đăng nhập']);
        exit;
    }

    if ($method === 'POST' && $path === '/login') {
        Csrf::verifyRequest();
        if (Auth::attempt((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''))) {
            flash('success', 'Đăng nhập thành công.');
            redirect('/dashboard');
        }
        render('auth/login', ['title' => 'Đăng nhập', 'errors' => ['Email hoặc mật khẩu chưa đúng.']]);
        exit;
    }

    if ($method === 'POST' && $path === '/logout') {
        Csrf::verifyRequest();
        Auth::logout();
        redirect('/');
    }

    if ($method === 'GET' && $path === '/dashboard') {
        $user = Auth::requireUser();
        $resumes = ResumeRepository::allForUser((int) $user['id']);
        render('dashboard', ['title' => 'CV của tôi', 'user' => $user, 'resumes' => $resumes]);
        exit;
    }

    if ($method === 'POST' && $path === '/resume/create') {
        Csrf::verifyRequest();
        $user = Auth::requireUser();
        $resumeId = ResumeRepository::create((int) $user['id'], (string) ($_POST['title'] ?? 'CV mới'));
        flash('success', 'Đã tạo CV mới. Hãy bổ sung thông tin của bạn.');
        redirect('/resume/edit?id=' . $resumeId);
    }

    if ($method === 'GET' && $path === '/resume/edit') {
        $user = Auth::requireUser();
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
        $user = Auth::requireUser();
        $resumeId = (int) ($_POST['id'] ?? 0);
        ResumeRepository::update($resumeId, (int) $user['id'], $_POST);
        flash('success', 'Đã lưu CV.');
        redirect('/resume/edit?id=' . $resumeId);
    }

    if ($method === 'POST' && $path === '/resume/delete') {
        Csrf::verifyRequest();
        $user = Auth::requireUser();
        ResumeRepository::delete((int) ($_POST['id'] ?? 0), (int) $user['id']);
        flash('success', 'Đã xóa CV.');
        redirect('/dashboard');
    }

    if ($method === 'POST' && $path === '/resume/clone') {
        Csrf::verifyRequest();
        $user = Auth::requireUser();
        $resumeId = ResumeRepository::cloneResume((int) ($_POST['id'] ?? 0), (int) $user['id']);
        flash('success', 'Đã tạo một bản sao để bạn tùy chỉnh theo vị trí khác.');
        redirect('/resume/edit?id=' . $resumeId);
    }

    if ($method === 'GET' && $path === '/resume/preview') {
        $user = Auth::requireUser();
        $resume = ResumeRepository::requireOwned((int) ($_GET['id'] ?? 0), (int) $user['id']);
        $sections = decode_sections((string) $resume['sections_json']);
        require dirname(__DIR__) . '/views/resume/preview.php';
        exit;
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
