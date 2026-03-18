from rest_framework import serializers
from .models import AgCotizaciones # Asegúrate de que el nombre coincida con tu models.py

class CotizacionSerializer(serializers.ModelSerializer):
    class Meta:
        model = AgCotizaciones
        fields = '__all__'