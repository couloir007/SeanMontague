/* global React */
function StatsBar({ stats }) {
  return (
    <div className="sm-stats">
      {stats.map((s, i) => (
        <div key={i} className={`sm-stats__item ${s.small ? 'sm-stats__item--small' : ''}`}>
          <span className="sm-stats__label">{s.label}</span>
          <span className={s.small ? 'sm-stats__value-sm' : 'sm-stats__value'}>{s.value}</span>
          {s.unit && <span className="sm-stats__unit">{s.unit}</span>}
        </div>
      ))}
    </div>
  );
}

function Blockquote({ children, cite }) {
  return (
    <blockquote className="sm-quote">
      <p>{children}</p>
      {cite && <cite>{cite}</cite>}
    </blockquote>
  );
}

function ArticleView({ onBack }) {
  const [progress, setProgress] = React.useState(0);
  const mapRef = React.useRef(null);

  React.useEffect(() => {
    const onScroll = () => {
      const h = document.documentElement.scrollHeight - window.innerHeight;
      setProgress(h > 0 ? Math.min(100, (window.scrollY / h) * 100) : 0);
    };
    window.addEventListener('scroll', onScroll);
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

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

  const stats = [
    { label: 'Distance', value: '12.4', unit: 'MILES' },
    { label: 'Elevation', value: '1,680', unit: 'FT GAIN' },
    { label: 'Summit', value: '3,271', unit: 'FT' },
    { label: 'Grade', value: 'Moderate', small: true, unit: 'BLUE SQ' },
    { label: 'Time', value: '2:15', unit: 'H:MM' },
  ];

  return (
    <>
      <div className="sm-progress" style={{ width: `${progress}%` }} />
      <div className="sm-article-header">
        <div className="sm-article-meta-top">
          <span className="sm-article-cat">Kingdom Trails</span>
          <span className="sm-article-date">MARCH 12, 2025</span>
          <span className="sm-article-diff">Moderate</span>
        </div>
        <h1 className="sm-article-title">FIRST RIDE AT KINGDOM TRAILS</h1>
        <p className="sm-article-sub">Darling Hill in April — mud, optimism, and what twelve miles of singletrack feels like after six months off the bike.</p>
        <StatsBar stats={stats} />
      </div>

      <div ref={mapRef} className="sm-article-map" />

      <div className="sm-article-body-wrap">
        <div className="sm-article-body">
          <h2>The season opener</h2>
          <p className="sm-drop-cap">April in the Northeast Kingdom is mud season with bike aspirations. The trails are officially closed, so you ride the high, dry ones — Sidewinder, Kitchel, the grassy loops at Darling Hill — and you ride them slower than you'd like, out of respect for the volunteers who cut the trail and the dirt that needs to dry.</p>
          <p>I hadn't been on a bike since October. Six months is a long time. The first half-hour was humbling in the way the first skate of the season is humbling: the muscles remember, the lungs do not.</p>

          <h3>Conditions</h3>
          <ul>
            <li>Ground: soft in the shaded sections, firm in the open</li>
            <li>Temperature: 54°F, high thin cloud</li>
            <li>Bugs: none. The early gift of early season.</li>
            <li>Other riders: two, both locals, nobody from out of town</li>
          </ul>

          <Blockquote cite="FIELD NOTE · APRIL 12">
            Riding a trail you know well after a long absence is like re-reading a favorite book. You find new things in the familiar lines, and old things you'd forgotten you loved.
          </Blockquote>

          <h2>The twelve-mile loop</h2>
          <p>The loop I rode is the one I always ride on the first day back — Sidewinder up, Kitchel traverse, down through the grassy meadows, back on dirt road. It's twelve miles, mostly blue, with one short black pitch that I walk every spring and clean every fall. Some ritual is embarrassing; some ritual is honest. This one is honest.</p>

          <h3>Trail by trail</h3>
          <p>Sidewinder climbs for a mile through hardwood before it opens up. The sight-lines are long and the corners are bermed well enough to carry speed. In April the trail has a faint yellow cast from the old leaves that didn't rot over winter. You can smell the wet wood.</p>
        </div>

        <aside className="sm-article-sidebar">
          <div className="sm-sidebar-card">
            <div className="sm-sidebar-card__head">Today's Loop</div>
            <div className="sm-sidebar-card__body">
              <ul className="sm-trail-list">
                <li><span className="sm-trail-name">Sidewinder</span><span className="sm-rating sm-rating--blue">Blue</span></li>
                <li><span className="sm-trail-name">Kitchel</span><span className="sm-rating sm-rating--green">Green</span></li>
                <li><span className="sm-trail-name">Heaven's Bench</span><span className="sm-rating sm-rating--blue">Blue</span></li>
                <li><span className="sm-trail-name">Tody's Tour</span><span className="sm-rating sm-rating--black">Black</span></li>
              </ul>
            </div>
          </div>
          <div className="sm-sidebar-card">
            <div className="sm-sidebar-card__head">Conditions</div>
            <div className="sm-sidebar-card__body">
              <div className="sm-cond-grid">
                <div><div className="sm-cond-label">Temp</div><div className="sm-cond-val">54°F</div></div>
                <div><div className="sm-cond-label">Wind</div><div className="sm-cond-val">Calm</div></div>
                <div><div className="sm-cond-label">Ground</div><div className="sm-cond-val">Mixed</div></div>
                <div><div className="sm-cond-label">Season</div><div className="sm-cond-val">Spring</div></div>
              </div>
            </div>
          </div>
          <div className="sm-sidebar-card">
            <div className="sm-sidebar-card__head">Related</div>
            <div className="sm-sidebar-card__body">
              <ul className="sm-sidebar-links">
                <li><a href="#" onClick={(e) => e.preventDefault()}>Kingdom Trails map</a></li>
                <li><a href="#" onClick={(e) => e.preventDefault()}>Trail ratings</a></li>
                <li><a href="#" onClick={(e) => e.preventDefault()}>Season opener gear</a></li>
              </ul>
            </div>
          </div>
        </aside>
      </div>

      <div className="sm-article-nav">
        <a href="#" onClick={(e) => { e.preventDefault(); onBack && onBack(); }} className="sm-article-nav__item">
          <span className="sm-article-nav__label">← Previous</span>
          <span className="sm-article-nav__title">A Season of Skiing Burke</span>
        </a>
        <a href="#" onClick={(e) => { e.preventDefault(); onBack && onBack(); }} className="sm-article-nav__item sm-article-nav__item--right">
          <span className="sm-article-nav__label">Next →</span>
          <span className="sm-article-nav__title">Year Three of the Food Forest</span>
        </a>
      </div>
    </>
  );
}

window.StatsBar = StatsBar;
window.Blockquote = Blockquote;
window.ArticleView = ArticleView;
