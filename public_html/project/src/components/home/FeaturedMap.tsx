import { useEffect, useRef } from 'react';

declare const L: any;

export default function FeaturedMap() {
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!ref.current || !window.L) return;
    const map = L.map(ref.current, {
      center: [44.5940, -71.9130], zoom: 14, scrollWheelZoom: false,
    });
    L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
      maxZoom: 17,
      attribution: '© <a href="https://opentopomap.org">OpenTopoMap</a> © <a href="https://openstreetmap.org">OSM</a>',
    }).addTo(map);

    L.geoJSON({
      type: 'Feature',
      geometry: {
        type: 'LineString',
        coordinates: [
          [-71.9105,44.5965],[-71.9150,44.5978],[-71.9230,44.5958],
          [-71.9270,44.5932],[-71.9240,44.5895],[-71.9180,44.5870],
          [-71.9110,44.5858],[-71.9060,44.5890],[-71.9045,44.5930],
          [-71.9070,44.5958],[-71.9105,44.5965],
        ],
      },
    }, { style: { color: '#3a5a40', weight: 3, opacity: 0.85, dashArray: '6,3' } })
      .addTo(map).bindPopup('Kingdom Trails — Darling Hill Loop (illustrative)');

    L.geoJSON({
      type: 'Feature',
      geometry: {
        type: 'LineString',
        coordinates: [
          [-71.9120,44.5958],[-71.9100,44.5930],[-71.9070,44.5905],
          [-71.9040,44.5880],[-71.9012,44.5847],
        ],
      },
    }, { style: { color: '#7a3410', weight: 2.5, opacity: 0.75, dashArray: '4,4' } })
      .addTo(map).bindPopup('Burke Mountain');

    const pin = (color: string) => L.divIcon({
      html: `<div style="width:10px;height:10px;border-radius:50%;background:${color};border:2px solid #fff;box-shadow:0 1px 6px rgba(0,0,0,0.3)"></div>`,
      iconSize: [10, 10], iconAnchor: [5, 5], className: '',
    });
    L.marker([44.5965, -71.9105], { icon: pin('#3a5a40') }).addTo(map).bindPopup('<strong>Kingdom Trails HQ</strong>');
    L.marker([44.5847, -71.9012], { icon: pin('#7a3410') }).addTo(map).bindPopup('<strong>Burke Mountain</strong><br>3,271 ft summit');

    return () => map.remove();
  }, []);

  return (
    <div
      ref={ref}
      style={{
        height: 440,
        border: '1px solid var(--border)',
      }}
    />
  );
}
