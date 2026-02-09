<?php
require_once "config.php";

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Prueba</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #121212; color: #eee; }
        .sidebar { min-height: 100vh; background: #1e1e1e; }
        .nav-link { color: #bbb; }
        .nav-link.active { background: #0d6efd; color: #fff; }
        main { padding: 20px; }
    </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <nav class="col-md-3 col-lg-2 d-md-block sidebar p-3">
      <h3 class="text-light mb-4">⚔️ Panel</h3>
      <div class="nav flex-column nav-pills">
		<a href="#" class="nav-link" data-section="pages/informacion">🖥️ Informacion</a>
        <a href="#" class="nav-link" data-section="pages/servers">🖥️ Servidores</a>
		<a href="#" class="nav-link" data-section="pages/procesos">🖥️ Procesos</a>
		<a href="#" class="nav-link" data-section="pages/users_online">🖥️ Usuarios Online</a>
		<a href="#" class="nav-link" data-section="pages/configuracion">⚙️ Configuración</a>
		<a href="#" class="nav-link" data-section="pages/rcon">⚙️ RCON</a>
		<a href="#" class="nav-link" data-section="pages/pak_mods">⚙️ PAK Mods</a>
		<a href="#" class="nav-link" data-section="pages/ue4ss_mods">⚙️ UE4SS Mods</a>
		<a href="#" class="nav-link" data-section="pages/paldefender">⚙️ Paldefender</a>
		<a href="#" class="nav-link" data-section="pages/update">⚙️ Actualizacion</a>
		<a href="#" class="nav-link" data-section="pages/ftp_manager">🌐 FTP Manager</a>
		<a href="#" class="nav-link" data-section="pages/donaciones">♥ Donaciones ♥</a>
        <a href="logout.php" class="nav-link text-danger">🚪 Cerrar Sesión</a>
      </div>
    </nav>

    <!-- Main content -->
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" id="main">
      <div class="text-center p-5 text-light">
        👋 Bienvenido al Panel de Palworld
      </div>

      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
      <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

      <script>
      $(function(){
        $('.sidebar .nav-link').on('click', function(e){
          const page = $(this).data('section') || $(this).data('page');
          if (!page) return; // enlaces normales como logout

          e.preventDefault();
          $('.sidebar .nav-link').removeClass('active');
          $(this).addClass('active');
          $('#main').html('<div class="p-5 text-center">Cargando…</div>');
          // si ya viene con 'pages/' no duplicar
          const path = page.startsWith('pages/') ? page : 'pages/' + page;
          $('#main').load(path + '.php');
        });
      });
      </script>
    </main>
  </div>
</div>
</body>
</html>
