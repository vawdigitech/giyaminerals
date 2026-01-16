<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Login</title>

  <!-- Fonts & Icons -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;300;500&display=swap" />
  <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free/css/all.min.css') }}" />

  <style>
  * {
    box-sizing: border-box;
  }

  body {
    margin: 0;
    height: 100vh;
    background: url('{{ asset("template/dist/img/boxed-bg-white.jpg") }}') no-repeat center center fixed;
    background-size: cover;
    font-family: 'Poppins', sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .login-box {
    background: rgba(22, 22, 22, 0.95); /* darker base */
    backdrop-filter: blur(14px);
    border-radius: 15px;
    padding: 40px 30px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 0 25px rgba(0, 0, 0, 0.6);
    color: #fff;
  }

  .login-box h2 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 25px;
    font-weight: 300;
  }

  .form-group {
    position: relative;
    margin-bottom: 25px;
  }

  .form-control {
    width: 100%;
    padding: 12px 45px 12px 15px;
    border-radius: 30px;
    background-color: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    color: #fff;
    font-size: 14px;
    outline: none;
    transition: background-color 0.3s;
  }

  .form-control::placeholder {
    color: #bbb;
  }

  .form-control:focus {
    background-color: rgba(255, 255, 255, 0.12);
  }

  .form-icon {
    position: absolute;
    right: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: #ccc;
    font-size: 14px;
  }

  .remember-submit {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
  }

  .remember-submit label {
    font-size: 14px;
    color: #bbb;
    margin-left: 5px;
  }

  .btn-login {
    width: 100%;
    padding: 10px;
    border-radius: 30px;
    border: none;
    background-color: #c97d06; /* blue highlight */
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s;
  }

  .btn-login:hover {
    background-color: #a47e28;
  }

  .footer-links {
    text-align: center;
    margin-top: 25px;
    font-size: 13px;
    color: #bbb;
  }

  .footer-links a {
    color: #fff;
    font-weight: bold;
    text-decoration: none;
  }

  .footer-links a:hover {
    text-decoration: underline;
  }

  .logo {
  text-align: center;
  margin-bottom: 20px;
}

.logo img {
  width: 100%; height: 100px; object-fit: cover; display: block;
  margin-bottom: 12px;
  filter: drop-shadow(0 0 6px rgba(0, 0, 0, 0.6));
  margin-left: auto;
  margin-right: auto;
}

  @media (max-width: 480px) {
    .login-box {
      padding: 30px 20px;
    }
  }
</style>

</head>
<body>
  <div class="login-box">
    <div class="logo">
    <img src="{{ asset('template/dist/img/logo1.png') }}" alt="Logo" />
    </div>
    <h2>LOGIN</h2>
    @if ($errors->any())
    <div style="
        background: rgba(255, 46, 46, 0.1);
        color: #ff2e2e;
        padding: 8px 12px;
        border-radius: 6px;
        margin-bottom: 15px;
        font-size: 13px;
        text-align: center;
        margin-top: -10px;
    ">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

    <form action="{{ route('login') }}" method="POST">
      @csrf

      <div class="form-group">
        <input type="email" name="email" class="form-control" placeholder="Email" required>
        <span class="form-icon fas fa-user"></span>
      </div>

      <div class="form-group">
        <input type="password" name="password" class="form-control" placeholder="Password" required>
        <span class="form-icon fas fa-lock"></span>
      </div>

      <div class="remember-submit">
        <label>
          <input type="checkbox" id="remember" name="remember"> Remember me
        </label>
      </div>

      <button type="submit" class="btn-login">Log In</button>
    </form>
  </div>

  <!-- JS -->
  <script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>
</body>
</html>
