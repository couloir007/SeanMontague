import { useEffect, useRef } from 'react';

declare const L: any;

export default function HeroMap() {
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!ref.current || !window.L) return;
    const map = L.map(ref.current, {
      center: [44.593, -71.918], zoom: 12,
      zoomControl: false, scrollWheelZoom: false,
      dragging: false, doubleClickZoom: false, keyboard: false,
    });
    L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
      maxZoom: 17,
      attribution: '© <a href="https://opentopomap.org">OpenTopoMap</a> © <a href="https://openstreetmap.org">OSM</a>',
    }).addTo(map);

    const pin = (color: string) => L.divIcon({
      html: `<div style="width:10px;height:10px;border-radius:50%;background:${color};border:2px solid #fff;box-shadow:0 1px 6px rgba(0,0,0,0.3)"></div>`,
      iconSize: [10, 10], iconAnchor: [5, 5], className: '',
    });

    L.marker([44.5965, -71.9105], { icon: pin('#3a5a40') }).addTo(map).bindPopup('<strong>Kingdom Trails HQ</strong><br>East Burke, VT');
    L.marker([44.5847, -71.9012], { icon: pin('#7a3410') }).addTo(map).bindPopup('<strong>Burke Mountain</strong><br>3,271 ft · 2,011 vert');
    L.marker([44.5958, -71.9120], { icon: pin('#4a7c9e') }).addTo(map).bindPopup('<strong>Burke, VT</strong>');
    L.marker([44.5920, -71.9250], { icon: pin('#7a7568') }).addTo(map).bindPopup('<strong>Darling Hill</strong>');
    requestAnimationFrame(() => map.invalidateSize());

    return () => map.remove();
  }, []);

  return <div ref={ref} style={{ position: 'absolute', inset: 0, zIndex: 0 }} />;
}
