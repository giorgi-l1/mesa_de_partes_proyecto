<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Trámite | UNJFSC</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    
    <style>
        .consulta-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 70px);
            padding: 20px;
        }
        .consulta-panel {
            max-width: 480px;
            width: 100%;
        }
        .consulta-header {
            text-align: center;
            margin-bottom: 25px;
        }
        .consulta-header h2 {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 10px;
        }
        .consulta-desc {
            color: #555;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        /* Estilos para armar el input del expediente */
        .input-grupo-expediente {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f4f6f9;
            padding: 5px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .input-grupo-expediente:focus-within {
            border-color: var(--dorado-arena);
        }
        .input-grupo-expediente span {
            font-weight: 600;
            color: var(--azul-oscuro);
            white-space: nowrap;
        }
        .input-grupo-expediente select,
        .input-grupo-expediente input {
            border: none !important;
            background: transparent !important;
            padding: 8px !important;
            box-shadow: none !important;
            margin: 0;
        }
        .input-grupo-expediente select:focus,
        .input-grupo-expediente input:focus {
            outline: none;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">UNJFSC <span>| Mesa de Partes</span></div>
        <div class="nav-links">
            <a href="index.php">Volver al Inicio</a>
        </div>
    </nav>

    <div class="consulta-wrapper">
        <div class="panel consulta-panel">
            
            <div class="consulta-header">
                <h2 class="panel-title">Consultar Expediente</h2>
                <p class="consulta-desc">Seleccione el año y digite el número de su expediente junto a su contraseña para ver el estado actual.</p>
            </div>

            <form action="p_consulta.php" method="POST">
                
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Número de Expediente</label>
                    <div class="input-grupo-expediente">
                        <span>EXP -</span>
                        <select name="anio" required>
                            <?php
                            // Genera los años desde el actual hasta el 2020
                            $anio_actual = date("Y");
                            for($i = $anio_actual; $i >= 2020; $i--) {
                                echo "<option value='$i'>$i</option>";
                            }
                            ?>
                        </select>
                        <span>-</span>
                        <input type="text" name="numero" placeholder="Ej. 12 o 000012" required autocomplete="off" maxlength="6" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label for="clave">Contraseña / Clave</label>
                    <input type="password" id="clave" name="clave" placeholder="Ingrese su contraseña" required>
                </div>

                <button type="submit" class="btn-submit">Consultar Estado</button>
                
            </form>
            
        </div>
    </div>

</body>
</html>