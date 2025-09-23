<?php
$section = $section ?? 'dashboard';
?>
<nav>
    <a href="index.php?section=dashboard" class="<?= $section === 'dashboard' ? 'active' : ''; ?>">Resumen</a>
    <?php if (isEngineer()): ?>
        <a href="index.php?section=funcionalidades" class="<?= $section === 'funcionalidades' ? 'active' : ''; ?>">Funcionalidades</a>
        <a href="index.php?section=errores" class="<?= $section === 'errores' ? 'active' : ''; ?>">Errores</a>
        <a href="index.php?section=asignadas" class="<?= $section === 'asignadas' ? 'active' : ''; ?>">Asignadas</a>
    <?php else: ?>
        <a href="index.php?section=mis-funcionalidades" class="<?= $section === 'mis-funcionalidades' ? 'active' : ''; ?>">Mis funcionalidades</a>
        <a href="index.php?section=mis-errores" class="<?= $section === 'mis-errores' ? 'active' : ''; ?>">Mis errores</a>
    <?php endif; ?>
    <a href="index.php?section=crear" class="<?= $section === 'crear' ? 'active' : ''; ?>">Crear solicitud</a>
    <a href="index.php?section=busqueda" class="<?= $section === 'busqueda' ? 'active' : ''; ?>">Busqueda avanzada</a>
    <a href="logout.php">Cerrar sesion</a>
    <form method="get" action="index.php" style="margin-left:auto; display:flex; gap:8px; align-items:center;">
        <input type="hidden" name="section" value="buscar">
        <input type="text" name="q" placeholder="Buscar por titulo" required>
        <button type="submit">Buscar</button>
    </form>
</nav>
