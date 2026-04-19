/* global React */
function Marquee() {
  const items = ['Kingdom Trails','Burke Mountain','Leaflet Mapping','Permaculture','Drupal','Northeast Kingdom','Open Source','Smithsonian NMNH'];
  const all = [...items, ...items];
  return (
    <div className="sm-marquee">
      <div className="sm-marquee__track">
        {all.map((it, i) => (
          <React.Fragment key={i}>
            <span className="sm-marquee__item">{it}</span>
            <span className="sm-marquee__sep">/</span>
          </React.Fragment>
        ))}
      </div>
    </div>
  );
}

function MapSection() {
  const mapRef = React.useRef(null);
  React.useEffect(() => {
    if (!mapRef.current || !window.L || mapRef.current._leaflet_id) return;
    const m = window.L.map(mapRef.current, { center: [44.5940, -71.9130], zoom: 14, scrollWheelZoom: false });
    window.L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', { maxZoom: 17, attribution: '© OpenTopoMap © OSM' }).addTo(m);
    window.L.geoJSON({
      type: 'Feature',
      geometry: { type: 'LineString', coordinates: [
        [-71.9105,44.5965],[-71.9150,44.5978],[-71.9230,44.5958],
        [-71.9270,44.5932],[-71.9240,44.5895],[-71.9180,44.5870],
        [-71.9110,44.5858],[-71.9060,44.5890],[-71.9045,44.5930],
        [-71.9070,44.5958],[-71.9105,44.5965]
      ]}
    }, { style: { color: '#3a5a40', weight: 3, opacity: 0.85, dashArray: '6,3' } }).addTo(m);
    requestAnimationFrame(() => m.invalidateSize());
  }, []);

  const links = ['Kingdom Trails Network', 'Burke Mountain Ski Lines', 'NEK Touring Routes', 'Burke Area Trails'];
  return (
    <section className="sm-section sm-map-section" id="maps">
      <span className="sm-section__label">Interactive Maps</span>
      <div className="sm-map-layout">
        <div>
          <h2 className="sm-section__title">The Kingdom, <em>mapped.</em></h2>
          <p className="sm-map-text">Custom Leaflet maps of the places I know best — Kingdom Trails singletrack, Burke Mountain terrain, back-road touring routes, and the land around Burke.</p>
          <p className="sm-map-text">Built with Leaflet.js, OpenTopoMap tiles, and GeoJSON from years of riding, skiing, and wandering. All open-source, all hand-crafted.</p>
          <div className="sm-map-links">
            {links.map(l => (
              <a key={l} href="#" onClick={(e) => e.preventDefault()} className="sm-map-link">
                <span>{l}</span><span className="sm-map-link__arrow">→</span>
              </a>
            ))}
          </div>
        </div>
        <div ref={mapRef} className="sm-featured-map" />
      </div>
    </section>
  );
}

window.Marquee = Marquee;
window.MapSection = MapSection;
