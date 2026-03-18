from rest_framework import viewsets
from .models import AgCotizaciones
from .serializers import CotizacionSerializer

class CotizacionViewSet(viewsets.ModelViewSet):
    queryset = AgCotizaciones.objects.all()
    serializer_class = CotizacionSerializer