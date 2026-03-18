# This is an auto-generated Django model module.
# You'll have to do the following manually to clean this up:
#   * Rearrange models' order
#   * Make sure each model has one field with primary_key=True
#   * Make sure each ForeignKey and OneToOneField has `on_delete` set to the desired behavior
#   * Remove `managed = False` lines if you wish to allow Django to create, modify, and delete the table
# Feel free to rename the models, but don't rename db_table values or field names.
from django.db import models


class AgCanceladasMotivo(models.Model):
    id_cotizacion = models.CharField(primary_key=True, max_length=255)
    motivo = models.TextField(blank=True, null=True)
    registrado_por = models.TextField(blank=True, null=True)
    actualizado_en = models.DateTimeField()

    class Meta:
        managed = False
        db_table = 'ag_canceladas_motivo'


class AgCotizaciones(models.Model):
    id_cotizacion = models.CharField(max_length=255, blank=True, null=True)
    nombre = models.TextField(blank=True, null=True)
    unidad_compra = models.TextField(blank=True, null=True)
    fecha_publicacion = models.CharField(max_length=100, blank=True, null=True)
    fecha_cierre = models.CharField(max_length=100, blank=True, null=True)
    estado = models.CharField(max_length=100, blank=True, null=True)
    estado_orig = models.TextField(blank=True, null=True)
    estado_convocatoria = models.TextField(blank=True, null=True)
    monto_disponible = models.DecimalField(max_digits=15, decimal_places=2, blank=True, null=True)
    moneda = models.CharField(max_length=10, blank=True, null=True)
    tipo_presupuesto = models.TextField(blank=True, null=True)
    cotizaciones_recibidas = models.IntegerField(blank=True, null=True)
    codigo_oc = models.CharField(max_length=255, blank=True, null=True)
    estado_oc = models.CharField(max_length=100, blank=True, null=True)
    anio = models.IntegerField(blank=True, null=True)
    cargado_en = models.DateTimeField(blank=True, null=True)

    class Meta:
        managed = False
        db_table = 'ag_cotizaciones'
