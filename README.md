# API Bounty Reports

## 📌 Descripción
**API Bounty Reports** es un servicio diseñado para recolectar información de reportes de seguridad provenientes de distintos proveedores (plataformas de bug bounty, programas privados, repositorios públicos, etc.) y unificarlos en una **base de datos categorizada y normalizada**.

El objetivo es ofrecer un **punto único de consulta** donde los reportes se presentan en un formato consistente (`JSON`), facilitando la integración con otras herramientas y flujos de trabajo.

---

## 🚀 Características principales
- 🔄 **Recolección automática** desde múltiples fuentes/proveedores.
- 🗂️ **Normalización de datos** para un formato estándar.
- 🧩 **Categorización** de reportes por tipo de vulnerabilidad, proveedor, severidad, etc.
- 📡 **API REST en JSON** para consumir los datos unificados.
- 📊 **Base unificada** que puede integrarse con dashboards, pipelines CI/CD o sistemas internos de seguridad.

---

## 📁 Formato de salida
La API expone resultados en formato `JSON`. 
Ejemplo:

```json
{
  "provider": "Bugcrowd",
  "title": "SQL Injection en endpoint de autenticación",
  "severity": "High",
  "category": "Injection",
  "status": "Resolved",
  "url": "https://bugcrowd.com/report/12345",
  "timestamp": "2025-09-16T12:34:56Z"
}
