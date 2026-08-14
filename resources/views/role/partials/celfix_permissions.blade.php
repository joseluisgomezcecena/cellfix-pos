{{-- ═══════════════════ MÓDULOS CELFIX ═══════════════════
     Permisos específicos de los módulos que desarrollamos para Celfix.
     Controlan si el ítem aparece en el sidebar y si el usuario puede entrar.
     Compatibilidad: si el rol tiene business_settings.access, todos estos
     quedan implícitos (ver AdminSidebarMenu.php). --}}
<hr>
<div class="row check_group" style="background-color:#e8f5e9; border-left:4px solid #43a047; padding:8px 0; border-radius:4px;">
    <div class="col-md-1">
        <h4 style="color:#1b5e20;">
            <i class="fas fa-star"></i><br>
            <small style="font-size:11px;">MÓDULOS<br>CELFIX</small>
        </h4>
    </div>
    <div class="col-md-2">
        <div class="checkbox">
            <label>
                <input type="checkbox" class="check_all input-icheck"> {{ __('role.select_all') }}
            </label>
        </div>
        <small class="text-muted" style="font-size:10px;">Cada permiso controla si el ítem aparece en el sidebar del usuario y si puede entrar al módulo.</small>
    </div>
    <div class="col-md-9">
        <div class="col-md-12"><strong style="color:#2e7d32;">GARANTÍAS</strong></div>
        <div class="col-md-12">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('permissions[]', 'celfix.warranty.access', in_array('celfix.warranty.access', $role_permissions ?? []), ['class' => 'input-icheck']); !!}
                    Acceso al módulo Garantías
                    <small class="text-muted">(sidebar: <em>Garantías</em> → Historial + Nueva)</small>
                </label>
            </div>
        </div>

        <div class="col-md-12" style="margin-top:6px;"><strong style="color:#2e7d32;">CORTES</strong></div>
        <div class="col-md-12">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('permissions[]', 'celfix.daily_cuts.view', in_array('celfix.daily_cuts.view', $role_permissions ?? []), ['class' => 'input-icheck']); !!}
                    Ver cortes diarios y semanales
                    <small class="text-muted">(sidebar: <em>Cortes diarios</em>)</small>
                </label>
            </div>
        </div>
        <div class="col-md-12">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('permissions[]', 'celfix.daily_cuts.denominations', in_array('celfix.daily_cuts.denominations', $role_permissions ?? []), ['class' => 'input-icheck']); !!}
                    Ver/capturar reporte de denominaciones
                    <small class="text-muted">(desglose de billetes por día)</small>
                </label>
            </div>
        </div>

        <div class="col-md-12" style="margin-top:6px;"><strong style="color:#2e7d32;">VENDEDORES</strong></div>
        <div class="col-md-12">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('permissions[]', 'celfix.vendors.weekly_report', in_array('celfix.vendors.weekly_report', $role_permissions ?? []), ['class' => 'input-icheck']); !!}
                    Reporte semanal de vendedores
                    <small class="text-muted">(vendedores puros solo ven su fila)</small>
                </label>
            </div>
        </div>
        <div class="col-md-12">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('permissions[]', 'celfix.vendors.commissions', in_array('celfix.vendors.commissions', $role_permissions ?? []), ['class' => 'input-icheck']); !!}
                    Metas y comisiones
                    <small class="text-muted">(configuración de metas por vendedor)</small>
                </label>
            </div>
        </div>

        <div class="col-md-12" style="margin-top:6px;"><strong style="color:#2e7d32;">TÉCNICOS Y REPARACIONES</strong></div>
        <div class="col-md-12">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('permissions[]', 'celfix.technicians.manage', in_array('celfix.technicians.manage', $role_permissions ?? []), ['class' => 'input-icheck']); !!}
                    Administrar técnicos
                    <small class="text-muted">(alta/edición/comisiones de técnicos)</small>
                </label>
            </div>
        </div>
        <div class="col-md-12">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('permissions[]', 'celfix.technicians.report', in_array('celfix.technicians.report', $role_permissions ?? []), ['class' => 'input-icheck']); !!}
                    Reporte de técnicos
                    <small class="text-muted">(desempeño por técnico)</small>
                </label>
            </div>
        </div>
        <div class="col-md-12">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('permissions[]', 'celfix.technicians.repair_orders', in_array('celfix.technicians.repair_orders', $role_permissions ?? []), ['class' => 'input-icheck']); !!}
                    Administrar reparaciones (recibir/entregar/cambiar técnico)
                    <small class="text-muted">(vista <em>/repair-orders/admin</em>)</small>
                </label>
            </div>
        </div>
    </div>
</div>
<hr>
