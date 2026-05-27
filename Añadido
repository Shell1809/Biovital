<?php
// vista/paciente/pac_catalogo.php
$nombre_usuario = $nombre_usuario ?? 'Usuario';
$id_paciente = $id_paciente ?? $_SESSION['usuario'] ?? 0;
?>

<style>
    .welcome-stats { background: linear-gradient(135deg, var(--bv-primary), var(--bv-accent)); border-radius: 20px; padding: 1.5rem; margin-bottom: 1.5rem; position: relative; overflow: hidden; }
    .quick-card { background: white; border-radius: 16px; padding: 1.5rem; text-align: center; transition: all 0.3s; cursor: pointer; text-decoration: none; display: block; border: 1px solid #eef2f6; }
    .quick-card:hover { transform: translateY(-5px); box-shadow: 0 12px 28px rgba(0,0,0,0.12); }
</style>

<div class="content-header">
    <div class="container-fluid"><h1><i class="fas fa-user-injured"></i> Panel del Paciente</h1></div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="welcome-stats text-white"><h2>Bienvenido, <?php echo htmlspecialchars($nombre_usuario); ?></h2></div>

        <div class="row mt-4">
            <div class="col-md-4"><a href="<?php echo APP_URL; ?>/paciente/recetas" class="quick-card"><h3>Historial Médico</h3></a></div>
            <div class="col-md-4"><a href="#seccionCitas" class="quick-card"><h3>Mis Citas</h3><p>Gestiona pagos y comprobantes.</p></a></div>
            <div class="col-md-4"><a href="<?php echo APP_URL; ?>/perfil" class="quick-card"><h3>Datos Personales</h3></a></div>
        </div>

        <div class="row mt-4" id="seccionCitas">
            <div class="col-12">
                <div class="card card-primary card-outline">
                    <div class="card-header"><h3 class="card-title">Mis Citas Agendadas</h3></div>
                    <div class="card-body">
                        <table class="table table-hover">
                            <thead><tr><th>Fecha</th><th>Especialidad</th><th>Referencia</th><th>Estado</th><th>Acciones</th></tr></thead>
                            <tbody>
                                <?php foreach ($citas as $c): ?>
                                <tr>
                                    <td><?php echo $c['fecha']; ?></td>
                                    <td><?php echo $c['especialidad']; ?></td>
                                    <td><?php echo $c['referencia_pago'] ?: 'Sin registrar'; ?></td>
                                    <td><span class="badge bg-info"><?php echo $c['estado_pago']; ?></span></td>
                                    <td>
                                        <?php if($c['estado_pago'] == 'Pendiente'): ?>
                                            <button class="btn btn-sm btn-primary btn-pagar-modal" data-id="<?php echo $c['id_cita']; ?>">Reportar Pago</button>
                                        <?php elseif($c['estado_pago'] == 'Confirmado'): ?>
                                            <a href="<?php echo APP_URL; ?>/paciente/pdf?id=<?php echo $c['id_cita']; ?>" target="_blank" class="btn btn-sm btn-success">PDF</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="pagoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formRegistrarPago" class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title">Registrar Referencia de Pago</h5></div>
            <div class="modal-body">
                <input type="hidden" name="id_cita" id="pago_id_cita">
                <div class="mb-3">
                    <label>Número de Referencia</label>
                    <input type="text" name="referencia" class="form-control" required placeholder="Ingrese el número de comprobante">
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Enviar Comprobante</button></div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Lógica para abrir el modal
    $(document).on('click', '.btn-pagar-modal', function() {
        $('#pago_id_cita').val($(this).data('id'));
        $('#pagoModal').modal('show');
    });

    // Envío del formulario
    $('#formRegistrarPago').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: APP_URL + '/PerfilController/registrarPago',
            type: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                alert('Pago reportado correctamente');
                location.reload();
            }
        });
    });
});
</script>
