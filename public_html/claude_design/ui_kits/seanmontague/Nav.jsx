/* global React */
const { useState, useEffect } = React;

function Nav({ current, go, mode = 'home' }) {
  const [scrolled, setScrolled] = useState(false);
  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 60);
    window.addEventListener('scroll', onScroll);
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  if (mode === 'article') {
    return (
      <nav className={`sm-nav ${scrolled ? 'sm-nav--scrolled' : ''}`}>
        <a href="#" onClick={(e) => { e.preventDefault(); go('home'); }} className="sm-nav__logo">
          Sean Montague <span>/ Burke, VT</span>
        </a>
        <a href="#" onClick={(e) => { e.preventDefault(); go('home'); }} className="sm-nav__back">
          Back to index
        </a>
      </nav>
    );
  }

  const links = ['Maps', 'Writing', 'Places', 'About'];
  return (
    <nav className={`sm-nav ${scrolled ? 'sm-nav--scrolled' : ''}`}>
      <a href="#" onClick={(e) => { e.preventDefault(); go('home'); }} className="sm-nav__logo">
        Sean Montague <span>/ Burke, VT</span>
      </a>
      <ul className="sm-nav__links">
        {links.map(l => (
          <li key={l}><a href={`#${l.toLowerCase()}`} className={current === l.toLowerCase() ? 'active' : ''}>{l}</a></li>
           ))}
        <li><a href="/claude_design/ui_kits/seanmontague/dest-alts.html" className="sm-nav__back">3 Approaches</a></li>
      </ul>
    </nav>
  );
}

window.Nav = Nav;
