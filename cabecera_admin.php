
<!-- cabecera_admin.php -->
<nav class="navbar">
    <div class="navbar-brand">UNJFSC <span>| Administración</span></div>
    <div class="nav-links">
        <a href="principal_admin.php">Visión Global</a>
        
        <div class="dropdown">
            <a class="dropbtn">Gestión de Oficinas ▼</a>
            <div class="dropdown-content">
                <a href="listado_oficina.php">Ver Oficinas</a>
            </div>
        </div>

        <div class="dropdown">
            <a class="dropbtn">Gestión de MP y Usuarios ▼</a>
            <div class="dropdown-content">
                <a href="gestion_usuarios.php">Ver Usuarios</a>
                <a href="gestion_mesas.php">Ver Mesas</a>
            </div>
        </div>

        <div class="dropdown">
            <a class="dropbtn">Gestor Tipo de Trámite ▼</a>
            <div class="dropdown-content">
                <a href="tipos_tramite.php">Ver Tipos de Trámite</a>
            </div>
        </div>

        <div class="dropdown">
            <a class="dropbtn">Estadísticas ▼</a>
            <div class="dropdown-content">
                <a href="est_oficinas.php">Oficinas</a>
                <a href="est_usuarios.php">Usuarios</a>
                <a href="est_mesa.php">Mesa</a>
                <a href="est_historico.php">Histórico</a>
            </div>
        </div>

        <a href="cerrar_session_mesa.php" class="btn-logout">Cerrar Sesión</a>
    </div>
</nav>