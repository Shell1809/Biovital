<?php

include_once 'Conexion.php';

class Cita {
    var $objetos;
    var $acceso;
    
    public function __construct() {
        try {
            $db = new Conexion();
            $this->acceso = $db->pdo;
        } catch(PDOException $e) {
            error_log("Error de conexión en Cita: " . $e->getMessage());
            $this->acceso = null;
        }
    }
    
    // ==================== MÉTODOS PRINCIPALES ====================
    
    function crear($datos) {
        try {
            $id_paciente = $datos['id_paciente'] ?? 0;
            $id_medico = $datos['id_medico'] ?? 0;
            $id_consultorio = $datos['id_consultorio'] ?? 0;
            $id_especialidad = $datos['id_especialidad'] ?? 0;
            $fecha_cita = $datos['fecha_cita'] ?? '';
            $hora_inicio = $datos['hora_inicio'] ?? '';
            $hora_fin = $datos['hora_fin'] ?? '';
            $motivo = $datos['motivo'] ?? '';
            $estado = $datos['estado'] ?? 'programada';
            $tipo_cita = $datos['tipo_cita'] ?? 'presencial';
            $observaciones = $datos['observaciones'] ?? '';
            
            if (empty($id_paciente) || empty($id_medico) || empty($fecha_cita) || empty($hora_inicio)) {
                return ['success' => false, 'message' => 'datos_incompletos'];
            }
            
            // Verificar disponibilidad
            if (!$this->verificarDisponibilidad($id_medico, $fecha_cita, $hora_inicio, $hora_fin)) {
                return ['success' => false, 'message' => 'horario_no_disponible'];
            }
            
            $sql = "INSERT INTO citas(
                id_paciente, id_medico, id_consultorio, id_especialidad,
                fecha_cita, hora_inicio, hora_fin, motivo, estado,
                tipo_cita, observaciones, fecha_creacion
            ) VALUES (
                :id_paciente, :id_medico, :id_consultorio, :id_especialidad,
                :fecha_cita, :hora_inicio, :hora_fin, :motivo, :estado,
                :tipo_cita, :observaciones, NOW()
            )";
            
            $query = $this->acceso->prepare($sql);
            $resultado = $query->execute(array(
                ':id_paciente' => $id_paciente,
                ':id_medico' => $id_medico,
                ':id_consultorio' => $id_consultorio,
                ':id_especialidad' => $id_especialidad,
                ':fecha_cita' => $fecha_cita,
                ':hora_inicio' => $hora_inicio,
                ':hora_fin' => $hora_fin,
                ':motivo' => $motivo,
                ':estado' => $estado,
                ':tipo_cita' => $tipo_cita,
                ':observaciones' => $observaciones
            ));
            
            if ($resultado) {
                $id_cita = $this->acceso->lastInsertId();
                return ['success' => true, 'message' => 'creado', 'id' => $id_cita];
            } else {
                return ['success' => false, 'message' => 'error_bd'];
            }
        } catch(PDOException $e) {
            error_log("Error en crear cita: " . $e->getMessage());
            return ['success' => false, 'message' => 'error_exception'];
        }
    }
    
    function editar($id_cita, $datos) {
        try {
            $fecha_cita = $datos['fecha_cita'] ?? '';
            $hora_inicio = $datos['hora_inicio'] ?? '';
            $hora_fin = $datos['hora_fin'] ?? '';
            $motivo = $datos['motivo'] ?? '';
            $estado = $datos['estado'] ?? 'programada';
            $tipo_cita = $datos['tipo_cita'] ?? 'presencial';
            $observaciones = $datos['observaciones'] ?? '';
            
            // Verificar disponibilidad si cambia fecha/hora
            $cita_actual = $this->obtenerPorId($id_cita);
            if ($cita_actual && ($cita_actual->fecha_cita != $fecha_cita || $cita_actual->hora_inicio != $hora_inicio)) {
                if (!$this->verificarDisponibilidad($cita_actual->id_medico, $fecha_cita, $hora_inicio, $hora_fin, $id_cita)) {
                    return ['success' => false, 'message' => 'horario_no_disponible'];
                }
            }
            
            $sql = "UPDATE citas SET 
                    fecha_cita = :fecha_cita,
                    hora_inicio = :hora_inicio,
                    hora_fin = :hora_fin,
                    motivo = :motivo,
                    estado = :estado,
                    tipo_cita = :tipo_cita,
                    observaciones = :observaciones
                    WHERE id_cita = :id_cita";
            
            $query = $this->acceso->prepare($sql);
            $resultado = $query->execute(array(
                ':id_cita' => $id_cita,
                ':fecha_cita' => $fecha_cita,
                ':hora_inicio' => $hora_inicio,
                ':hora_fin' => $hora_fin,
                ':motivo' => $motivo,
                ':estado' => $estado,
                ':tipo_cita' => $tipo_cita,
                ':observaciones' => $observaciones
            ));
            
            if ($resultado) {
                return ['success' => true, 'message' => 'editado'];
            } else {
                return ['success' => false, 'message' => 'error_bd'];
            }
        } catch(PDOException $e) {
            error_log("Error en editar cita: " . $e->getMessage());
            return ['success' => false, 'message' => 'error_exception'];
        }
    }
    
    function cancelar($id_cita, $motivo_cancelacion = '') {
        try {
            $sql = "UPDATE citas SET 
                    estado = 'cancelada',
                    motivo_cancelacion = :motivo_cancelacion,
                    fecha_cancelacion = NOW()
                    WHERE id_cita = :id_cita";
            
            $query = $this->acceso->prepare($sql);
            $resultado = $query->execute(array(
                ':id_cita' => $id_cita,
                ':motivo_cancelacion' => $motivo_cancelacion
            ));
            
            if ($resultado) {
                return ['success' => true, 'message' => 'cancelada'];
            } else {
                return ['success' => false, 'message' => 'error_bd'];
            }
        } catch(PDOException $e) {
            error_log("Error en cancelar cita: " . $e->getMessage());
            return ['success' => false, 'message' => 'error_exception'];
        }
    }
    
    function completar($id_cita) {
        try {
            $sql = "UPDATE citas SET 
                    estado = 'completada',
                    fecha_completada = NOW()
                    WHERE id_cita = :id_cita";
            
            $query = $this->acceso->prepare($sql);
            $resultado = $query->execute(array(':id_cita' => $id_cita));
            
            if ($resultado) {
                return ['success' => true, 'message' => 'completada'];
            } else {
                return ['success' => false, 'message' => 'error_bd'];
            }
        } catch(PDOException $e) {
            error_log("Error en completar cita: " . $e->getMessage());
            return ['success' => false, 'message' => 'error_exception'];
        }
    }
    
    // ==================== MÉTODOS DE CONSULTA ====================
    
    function obtenerPorId($id_cita) {
        try {
            $sql = "SELECT c.*, 
                           CONCAT(rp.nombre_paciente, ' ', rp.apellido_paciente) as nombre_paciente,
                           CONCAT(rm.nombre_medico, ' ', rm.apellido_medico) as nombre_medico,
                           c.nombre as consultorio_nombre,
                           e.nombre as especialidad_nombre
                    FROM citas c
                    LEFT JOIN registro_paciente rp ON c.id_paciente = rp.id_paciente
                    LEFT JOIN registro_medico rm ON c.id_medico = rm.id_medico
                    LEFT JOIN consultorios c ON c.id_consultorio = c.id_consultorio
                    LEFT JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                    WHERE c.id_cita = :id_cita";
            
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':id_cita' => $id_cita));
            return $query->fetch(PDO::FETCH_OBJ);
        } catch(PDOException $e) {
            error_log("Error en obtenerPorId: " . $e->getMessage());
            return null;
        }
    }
    
    function listarPorPaciente($id_paciente, $estado = null) {
        try {
            $sql = "SELECT c.*, 
                           CONCAT(rm.nombre_medico, ' ', rm.apellido_medico) as nombre_medico,
                           c.nombre as consultorio_nombre,
                           e.nombre as especialidad_nombre
                    FROM citas c
                    LEFT JOIN registro_medico rm ON c.id_medico = rm.id_medico
                    LEFT JOIN consultorios c ON c.id_consultorio = c.id_consultorio
                    LEFT JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                    WHERE c.id_paciente = :id_paciente";
            
            if ($estado) {
                $sql .= " AND c.estado = :estado";
            }
            
            $sql .= " ORDER BY c.fecha_cita DESC, c.hora_inicio DESC";
            
            $query = $this->acceso->prepare($sql);
            $params = array(':id_paciente' => $id_paciente);
            if ($estado) {
                $params[':estado'] = $estado;
            }
            $query->execute($params);
            return $query->fetchAll();
        } catch(PDOException $e) {
            error_log("Error en listarPorPaciente: " . $e->getMessage());
            return array();
        }
    }
    
    function listarPorMedico($id_medico, $fecha = null, $estado = null) {
        try {
            $sql = "SELECT c.*, 
                           CONCAT(rp.nombre_paciente, ' ', rp.apellido_paciente) as nombre_paciente,
                           rp.cedula_paciente,
                           rp.telefono_paciente,
                           c.nombre as consultorio_nombre,
                           e.nombre as especialidad_nombre
                    FROM citas c
                    LEFT JOIN registro_paciente rp ON c.id_paciente = rp.id_paciente
                    LEFT JOIN consultorios c ON c.id_consultorio = c.id_consultorio
                    LEFT JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                    WHERE c.id_medico = :id_medico";
            
            if ($fecha) {
                $sql .= " AND c.fecha_cita = :fecha";
            }
            
            if ($estado) {
                $sql .= " AND c.estado = :estado";
            }
            
            $sql .= " ORDER BY c.fecha_cita ASC, c.hora_inicio ASC";
            
            $query = $this->acceso->prepare($sql);
            $params = array(':id_medico' => $id_medico);
            if ($fecha) {
                $params[':fecha'] = $fecha;
            }
            if ($estado) {
                $params[':estado'] = $estado;
            }
            $query->execute($params);
            return $query->fetchAll();
        } catch(PDOException $e) {
            error_log("Error en listarPorMedico: " . $e->getMessage());
            return array();
        }
    }
    
    function listarPorFecha($fecha, $id_especialidad = null) {
        try {
            $sql = "SELECT c.*, 
                           CONCAT(rp.nombre_paciente, ' ', rp.apellido_paciente) as nombre_paciente,
                           CONCAT(rm.nombre_medico, ' ', rm.apellido_medico) as nombre_medico,
                           e.nombre as especialidad_nombre
                    FROM citas c
                    LEFT JOIN registro_paciente rp ON c.id_paciente = rp.id_paciente
                    LEFT JOIN registro_medico rm ON c.id_medico = rm.id_medico
                    LEFT JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                    WHERE c.fecha_cita = :fecha AND c.estado = 'programada'";
            
            if ($id_especialidad) {
                $sql .= " AND c.id_especialidad = :id_especialidad";
            }
            
            $sql .= " ORDER BY c.hora_inicio ASC";
            
            $query = $this->acceso->prepare($sql);
            $params = array(':fecha' => $fecha);
            if ($id_especialidad) {
                $params[':id_especialidad'] = $id_especialidad;
            }
            $query->execute($params);
            return $query->fetchAll();
        } catch(PDOException $e) {
            error_log("Error en listarPorFecha: " . $e->getMessage());
            return array();
        }
    }
    
    function listarProximas($id_paciente, $limit = 5) {
        try {
            $sql = "SELECT c.*, 
                           CONCAT(rm.nombre_medico, ' ', rm.apellido_medico) as nombre_medico,
                           e.nombre as especialidad_nombre
                    FROM citas c
                    LEFT JOIN registro_medico rm ON c.id_medico = rm.id_medico
                    LEFT JOIN especialidades e ON c.id_especialidad = e.id_especialidad
                    WHERE c.id_paciente = :id_paciente 
                    AND c.estado = 'programada'
                    AND (c.fecha_cita > CURDATE() OR (c.fecha_cita = CURDATE() AND c.hora_inicio > CURTIME()))
                    ORDER BY c.fecha_cita ASC, c.hora_inicio ASC
                    LIMIT :limit";
            
            $query = $this->acceso->prepare($sql);
            $query->bindValue(':id_paciente', $id_paciente, PDO::PARAM_INT);
            $query->bindValue(':limit', $limit, PDO::PARAM_INT);
            $query->execute();
            return $query->fetchAll();
        } catch(PDOException $e) {
            error_log("Error en listarProximas: " . $e->getMessage());
            return array();
        }
    }
    
    // ==================== MÉTODOS DE DISPONIBILIDAD ====================
    
    function verificarDisponibilidad($id_medico, $fecha, $hora_inicio, $hora_fin, $excluir_cita = null) {
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM citas 
                    WHERE id_medico = :id_medico 
                    AND fecha_cita = :fecha 
                    AND estado = 'programada'
                    AND ((hora_inicio < :hora_fin AND hora_fin > :hora_inicio))";
            
            if ($excluir_cita) {
                $sql .= " AND id_cita != :excluir_cita";
            }
            
            $query = $this->acceso->prepare($sql);
            $params = array(
                ':id_medico' => $id_medico,
                ':fecha' => $fecha,
                ':hora_inicio' => $hora_inicio,
                ':hora_fin' => $hora_fin
            );
            
            if ($excluir_cita) {
                $params[':excluir_cita'] = $excluir_cita;
            }
            
            $query->execute($params);
            $resultado = $query->fetch(PDO::FETCH_OBJ);
            
            return $resultado->total == 0;
        } catch(PDOException $e) {
            error_log("Error en verificarDisponibilidad: " . $e->getMessage());
            return false;
        }
    }
    
    function obtenerHorariosDisponibles($id_medico, $fecha) {
        try {
            // Obtener horarios del médico
            $sql = "SELECT ch.dia_semana, ch.hora_inicio, ch.hora_fin
                    FROM consultorio_horarios ch
                    WHERE ch.id_medico = :id_medico AND ch.activo = 1";
            
            $query = $this->acceso->prepare($sql);
            $query->execute(array(':id_medico' => $id_medico));
            $horarios = $query->fetchAll();
            
            // Obtener día de la semana de la fecha
            $dia_semana = date('l', strtotime($fecha));
            $dias_espanol = array(
                'Monday' => 'Lunes',
                'Tuesday' => 'Martes',
                'Wednesday' => 'Miércoles',
                'Thursday' => 'Jueves',
                'Friday' => 'Viernes',
                'Saturday' => 'Sábado',
                'Sunday' => 'Domingo'
            );
            $dia_es = $dias_espanol[$dia_semana] ?? '';
            
            // Filtrar horarios del día
            $horarios_dia = array_filter($horarios, function($h) use ($dia_es) {
                return $h->dia_semana == $dia_es;
            });
            
            // Obtener citas ya programadas
            $sql = "SELECT hora_inicio, hora_fin
                    FROM citas
                    WHERE id_medico = :id_medico 
                    AND fecha_cita = :fecha 
                    AND estado = 'programada'";
            
            $query = $this->acceso->prepare($sql);
            $query->execute(array(
                ':id_medico' => $id_medico,
                ':fecha' => $fecha
            ));
            $citas = $query->fetchAll();
            
            return array(
                'horarios_disponibles' => $horarios_dia,
                'citas_programadas' => $citas
            );
        } catch(PDOException $e) {
            error_log("Error en obtenerHorariosDisponibles: " . $e->getMessage());
            return array();
        }
    }
    
    // ==================== MÉTODOS DE ESTADÍSTICAS ====================
    
    function contarPorEstado($estado, $fecha_inicio = null, $fecha_fin = null) {
        try {
            $sql = "SELECT COUNT(*) as total FROM citas WHERE estado = :estado";
            
            if ($fecha_inicio && $fecha_fin) {
                $sql .= " AND fecha_cita BETWEEN :fecha_inicio AND :fecha_fin";
            }
            
            $query = $this->acceso->prepare($sql);
            $params = array(':estado' => $estado);
            
            if ($fecha_inicio && $fecha_fin) {
                $params[':fecha_inicio'] = $fecha_inicio;
                $params[':fecha_fin'] = $fecha_fin;
            }
            
            $query->execute($params);
            $resultado = $query->fetch(PDO::FETCH_OBJ);
            return $resultado->total ?? 0;
        } catch(PDOException $e) {
            error_log("Error en contarPorEstado: " . $e->getMessage());
            return 0;
        }
    }
    
    function obtenerEstadisticas($id_medico = null, $fecha_inicio = null, $fecha_fin = null) {
        try {
            $stats = array();
            
            $where = "";
            $params = array();
            
            if ($id_medico) {
                $where .= " WHERE id_medico = :id_medico";
                $params[':id_medico'] = $id_medico;
            }
            
            if ($fecha_inicio && $fecha_fin) {
                $where .= ($where ? " AND" : " WHERE") . " fecha_cita BETWEEN :fecha_inicio AND :fecha_fin";
                $params[':fecha_inicio'] = $fecha_inicio;
                $params[':fecha_fin'] = $fecha_fin;
            }
            
            // Total de citas
            $sql = "SELECT COUNT(*) as total FROM citas $where";
            $query = $this->acceso->prepare($sql);
            $query->execute($params);
            $stats['total_citas'] = $query->fetch(PDO::FETCH_OBJ)->total ?? 0;
            
            // Citas por estado
            $estados = array('programada', 'completada', 'cancelada', 'no_asistio');
            foreach ($estados as $estado) {
                $sql = "SELECT COUNT(*) as total FROM citas $where AND estado = :estado";
                $query = $this->acceso->prepare($sql);
                $query->execute(array_merge($params, array(':estado' => $estado)));
                $stats['citas_' . $estado] = $query->fetch(PDO::FETCH_OBJ)->total ?? 0;
            }
            
            return $stats;
        } catch(PDOException $e) {
            error_log("Error en obtenerEstadisticas: " . $e->getMessage());
            return array();
        }
    }
}
?>
