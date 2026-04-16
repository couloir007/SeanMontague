import { useEffect, useRef } from 'react';
import { routeCoords } from '../../data/routeData';

declare const L: any;

interface Props {
  onMarkerRef: (marker: any) => void;
}

export default function ArticleMap({ onMarkerRef }: Props) {
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!ref.current || !window.L) return;

    const map = L.map(ref.current, {
      center: [44.5917, -71.9178], zoom: 13, scrollWheelZoom: false,
    });
    L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
      maxZoom: 17,
      attribution: '© <a href="https://opentopomap.org">OpenTopoMap</a> © <a href="https://openstreetmap.org">OSM</a>',
    }).addTo(map);

    L.geoJSON(
      { type: 'Feature', geometry: { type: 'LineString', coordinates: routeCoords } },
      { style: { color: '#3a5a40', weight: 3, opacity: 0.9 } }
    ).addTo(map);

    const pin = (color: string) => L.divIcon({
      html: `<div style="width:10px;height:10px;border-radius:50%;background:${color};border:2px solid #fff;box-shadow:0 1px 6px rgba(0,0,0,0.3)"></div>`,
      iconSize: [10, 10], iconAnchor: [5, 5], className: '',
    });

    const waypoints = [
      { latlng: [44.588812, -71.945434] as [number,number], label: '<strong>Start / Finish</strong><br>812 ft', color: '#3a5a40' },
      { latlng: [44.607766, -71.893419] as [number,number], label: '<strong>High Point</strong><br>1,595 ft', color: '#7a3410' },
      { latlng: [44.613136, -71.898428] as [number,number], label: '<strong>Ridge</strong><br>1,302 ft', color: '#4a7c9e' },
    ];
    waypoints.forEach(wp => L.marker(wp.latlng, { icon: pin(wp.color) }).addTo(map).bindPopup(wp.label));

    const scrub = L.circleMarker([44.588812, -71.945434], {
      radius: 7, color: '#fff', weight: 2,
      fillColor: '#3a5a40', fillOpacity: 1, interactive: false,
    }).addTo(map);
    onMarkerRef(scrub);

    return () => map.remove();
  }, []);

  return (
    <div ref={ref} style={{ width: '100%', height: 480, borderBottom: '1px solid var(--border)' }} />
  );
}
