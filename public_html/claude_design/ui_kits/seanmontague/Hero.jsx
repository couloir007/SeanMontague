/* global React */
const { useEffect, useRef } = React;

function Hero({ onExplore, onRead }) {
  const mapRef = useRef(null);

  useEffect(() => {
    if (!mapRef.current || !window.L || mapRef.current._leaflet_id) return;
    const m = window.L.map(mapRef.current, {
      center: [44.593, -71.918], zoom: 12,
      zoomControl: false, scrollWheelZoom: false,
      dragging: false, doubleClickZoom: false, keyboard: false
    });
    window.L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
      maxZoom: 17,
      attribution: '© OpenTopoMap © OSM'
    }).addTo(m);
    const pin = (c) => window.L.divIcon({
      html: `<div style="width:10px;height:10px;border-radius:50%;background:${c};border:2px solid #fff"></div>`,
      iconSize: [10,10], iconAnchor: [5,5], className: ''
    });
    window.L.marker([44.5965, -71.9105], { icon: pin('#3a5a40') }).addTo(m).bindPopup('<strong>Kingdom Trails HQ</strong>');
    window.L.marker([44.5847, -71.9012], { icon: pin('#7a3410') }).addTo(m).bindPopup('<strong>Burke Mountain</strong>');
    window.L.marker([44.5958, -71.9120], { icon: pin('#4a7c9e') }).addTo(m).bindPopup('<strong>Burke, VT</strong>');
    requestAnimationFrame(() => m.invalidateSize());
  }, []);

  const cats = [
    { label: 'Kingdom Trails', svg: <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zM2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/> },
    { label: 'Burke Mountain', svg: <path d="M3 17l5-10 4 6 3-4 6 8"/> },
    { label: 'Permaculture', svg: <g><circle cx="12" cy="8" r="4"/><path d="M12 12v10M8 22h8"/></g> },
    { label: 'Leaflet Maps', svg: <g><path d="M9 3L3 6v15l6-3 6 3 6-3V3l-6 3-6-3z"/><path d="M9 3v15M15 6v15"/></g> },
    { label: 'Writing', svg: <g><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></g> },
  ];

  return (
    <section className="sm-hero">
      <div ref={mapRef} className="sm-hero__map" />
      <div className="sm-hero__overlay" />
      <div className="sm-hero__content">
        <p className="sm-hero__eyebrow">Burke, Vermont · Northeast Kingdom</p>
        <h1 className="sm-hero__name">SEAN<br/>MONTAGUE</h1>
        <p className="sm-hero__tagline">Maps, trails, and growing things. Web developer at the Smithsonian, living and exploring in the Northeast Kingdom.</p>
        <div className="sm-hero__cta">
          <a href="#maps" onClick={(e) => { e.preventDefault(); onExplore && onExplore(); }} className="sm-btn-primary">Explore the Maps</a>
          <a href="#writing" onClick={(e) => { e.preventDefault(); onRead && onRead(); }} className="sm-btn-ghost">Read the Writing</a>
        </div>
      </div>
      <div className="sm-hero__cats">
        {cats.map(c => (
          <a key={c.label} href="#" onClick={(e) => e.preventDefault()} className="sm-hero__cat">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" className="sm-hero__cat-icon">{c.svg}</svg>
            {c.label}
          </a>
        ))}
      </div>
    </section>
  );
}

window.Hero = Hero;
