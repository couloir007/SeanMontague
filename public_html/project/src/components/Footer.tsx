const FOOTER_LINKS = [
  { label: 'GitHub',     href: '#' },
  { label: 'Drupal.org', href: '#' },
  { label: 'LinkedIn',   href: '#' },
] as const;

export default function Footer() {
  return (
    <footer
      className="on-dark"
      style={{
        background: 'var(--bright)',
        borderTop: '1px solid rgba(255,255,255,0.1)',
      }}
    >
      <div style={{
        display: 'flex', justifyContent: 'space-between', alignItems: 'center',
        flexWrap: 'wrap', gap: 12,
        padding: 'clamp(16px, 3vw, 24px) clamp(16px, 5vw, 48px)',
      }}>
        <span style={{
          fontFamily: "'DM Mono', monospace", fontSize: 11,
          color: 'rgba(255,255,255,0.3)',
          letterSpacing: '0.08em',
        }}>
          Sean Montague · Burke, VT
        </span>

        <nav aria-label="Footer navigation">
          <ul role="list" style={{ display: 'flex', gap: 20, listStyle: 'none', margin: 0, padding: 0, flexWrap: 'wrap' }}>
            {FOOTER_LINKS.map(link => (
              <li key={link.label}>
                <a
                  href={link.href}
                  style={{
                    fontFamily: "'DM Mono', monospace", fontSize: 10,
                    letterSpacing: '0.14em', textTransform: 'uppercase',
                    color: 'rgba(255,255,255,0.3)', textDecoration: 'none',
                    transition: 'color 0.2s',
                    display: 'inline-flex', alignItems: 'center', minHeight: 44,
                  }}
                  onFocus={e  => (e.currentTarget.style.color = 'rgba(255,255,255,0.7)')}
                  onBlur={e   => (e.currentTarget.style.color = 'rgba(255,255,255,0.3)')}
                  onMouseEnter={e => (e.currentTarget.style.color = 'rgba(255,255,255,0.7)')}
                  onMouseLeave={e => (e.currentTarget.style.color = 'rgba(255,255,255,0.3)')}
                >
                  {link.label}
                </a>
              </li>
            ))}
          </ul>
        </nav>

        <span style={{
          fontFamily: "'DM Mono', monospace", fontSize: 10,
          color: 'rgba(255,255,255,0.2)', letterSpacing: '0.08em',
        }}>
          © 2025 Sean Montague
        </span>
      </div>
    </footer>
  );
}
