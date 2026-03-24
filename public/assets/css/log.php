*,
*::before,
*::after {
    box-sizing: border-box;
}

html {
    font-size: 16px;
}

body {
    margin: 0;
    background: #fff;
    color: #111;
    line-height: 1.5;
    font-family: "Chicago", "Charcoal", "Geneva", "Verdana", sans-serif;
}

a {
    color: inherit;
}

.log-page {
    max-width: 860px;
    margin: 0 auto;
    padding: 2rem 1.2rem 4rem;
}

.log-header {
    margin-bottom: 2rem;
}

.log-header h1 {
    margin: 0 0 0.25rem;
    font-size: 2rem;
}

.log-header p {
    margin: 0;
}

.log-section {
    margin-bottom: 2rem;
}

.log-section h2 {
    margin: 0 0 0.6rem;
    font-size: 1.15rem;
    text-transform: lowercase;
}

.log-meta {
    margin: 0 0 0.75rem;
    opacity: 0.8;
}

.ascii-block {
    margin: 0;
    padding: 0;
    white-space: pre-wrap;
    font-family: "Monaco", "Courier New", monospace;
    font-size: 0.95rem;
    line-height: 1.45;
}

.log-list {
    margin: 0;
    padding-left: 1.3rem;
}

.log-list li + li {
    margin-top: 0.45rem;
}

.month-block {
    padding-top: 0.25rem;
    border-top: 1px solid #111;
}

@media (max-width: 720px) {
    .log-page {
        padding: 1.25rem 0.9rem 3rem;
    }

    .log-header h1 {
        font-size: 1.6rem;
    }

    .ascii-block {
        font-size: 0.88rem;
    }
}
