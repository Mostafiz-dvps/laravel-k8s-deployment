<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel Kubernetes Deployment</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; }
        
        .hero { background: linear-gradient(135deg, #1e293b 0%, #0f172a 50%, #1a1a2e 100%); padding: 60px 20px; text-align: center; border-bottom: 1px solid #334155; }
        .badge { display: inline-block; background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 16px; letter-spacing: 1px; }
        .hero h1 { font-size: 2.5rem; font-weight: 700; color: #f8fafc; margin-bottom: 12px; }
        .hero h1 span { color: #f97316; }
        .hero p { color: #94a3b8; font-size: 1.1rem; max-width: 600px; margin: 0 auto 24px; }
        .status-bar { display: flex; justify-content: center; gap: 24px; flex-wrap: wrap; }
        .status-item { display: flex; align-items: center; gap: 8px; background: #1e293b; padding: 8px 16px; border-radius: 8px; border: 1px solid #334155; font-size: 14px; }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

        .container { max-width: 1100px; margin: 0 auto; padding: 40px 20px; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 24px; }
        .card h3 { font-size: 14px; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; }
        .tag { display: inline-block; background: #0f172a; border: 1px solid #334155; color: #94a3b8; padding: 4px 10px; border-radius: 6px; font-size: 12px; margin: 3px; }
        .tag.green { border-color: #10b981; color: #10b981; }
        .tag.orange { border-color: #f97316; color: #f97316; }
        .tag.blue { border-color: #3b82f6; color: #3b82f6; }
        .tag.purple { border-color: #a855f7; color: #a855f7; }

        .section { margin-bottom: 40px; }
        .section-title { font-size: 1.3rem; font-weight: 600; color: #f8fafc; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #334155; }

        .timeline { position: relative; padding-left: 24px; }
        .timeline::before { content: ''; position: absolute; left: 6px; top: 0; bottom: 0; width: 2px; background: #334155; }
        .timeline-item { position: relative; margin-bottom: 24px; }
        .timeline-item::before { content: ''; position: absolute; left: -21px; top: 6px; width: 10px; height: 10px; border-radius: 50%; background: #f97316; border: 2px solid #0f172a; }
        .timeline-item h4 { color: #f8fafc; font-size: 15px; margin-bottom: 4px; }
        .timeline-item p { color: #64748b; font-size: 13px; }

        .challenge { background: #1e293b; border-left: 3px solid #ef4444; border-radius: 8px; padding: 16px; margin-bottom: 12px; }
        .challenge h4 { color: #fca5a5; font-size: 14px; margin-bottom: 4px; }
        .challenge p { color: #94a3b8; font-size: 13px; }
        .challenge .fix { color: #10b981; font-size: 13px; margin-top: 6px; }

        .improvement { background: #1e293b; border-left: 3px solid #3b82f6; border-radius: 8px; padding: 16px; margin-bottom: 12px; }
        .improvement h4 { color: #93c5fd; font-size: 14px; margin-bottom: 4px; }
        .improvement p { color: #94a3b8; font-size: 13px; }

        .arch { background: #0f172a; border: 1px solid #334155; border-radius: 12px; padding: 24px; font-family: monospace; font-size: 13px; color: #94a3b8; line-height: 2; }
        .arch .highlight { color: #f97316; }
        .arch .green { color: #10b981; }
        .arch .blue { color: #3b82f6; }

        .footer { text-align: center; padding: 30px; border-top: 1px solid #334155; color: #475569; font-size: 13px; }
        .footer a { color: #f97316; text-decoration: none; }

        .metric { text-align: center; }
        .metric .value { font-size: 2rem; font-weight: 700; color: #f97316; }
        .metric .label { font-size: 12px; color: #64748b; margin-top: 4px; }

        @media (max-width: 600px) {
            .hero h1 { font-size: 1.8rem; }
            .status-bar { gap: 12px; }
        }
    </style>
</head>
<body>

<div class="hero">
    <div class="badge">✅ LIVE ON KUBERNETES</div>
    <h1>Laravel <span>Kubernetes</span> Deployment</h1>
    <p>Production-ready Laravel 13 running on a self-hosted kubeadm cluster with full CI/CD pipeline</p>
    <div class="status-bar">
        <div class="status-item"><span class="dot"></span> App Online</div>
        <div class="status-item"><span class="dot"></span> 2 Pods Running</div>
        <div class="status-item"><span class="dot"></span> CI/CD Active</div>
        <div class="status-item"><span class="dot"></span> Monitoring Live</div>
    </div>
</div>

<div class="container">

    <!-- Metrics -->
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); margin-bottom: 40px;">
        <div class="card metric"><div class="value">3</div><div class="label">K8s Nodes</div></div>
        <div class="card metric"><div class="value">2</div><div class="label">Control Planes</div></div>
        <div class="card metric"><div class="value">2</div><div class="label">App Replicas</div></div>
        <div class="card metric"><div class="value">v1.30</div><div class="label">Kubernetes</div></div>
        <div class="card metric"><div class="value">PHP 8.3</div><div class="label">Runtime</div></div>
        <div class="card metric"><div class="value">100%</div><div class="label">Automated</div></div>
    </div>

    <!-- Stack -->
    <div class="grid">
        <div class="card">
            <h3>🏗️ Infrastructure</h3>
            <span class="tag green">Ubuntu 24.04</span>
            <span class="tag green">Multipass/KVM</span>
            <span class="tag green">kubeadm v1.30</span>
            <span class="tag green">Calico CNI</span>
            <span class="tag green">MetalLB</span>
            <span class="tag green">ingress-nginx</span>
            <span class="tag green">cert-manager</span>
            <span class="tag green">local-path-provisioner</span>
        </div>
        <div class="card">
            <h3>🚀 Application</h3>
            <span class="tag orange">Laravel 13</span>
            <span class="tag orange">PHP 8.3</span>
            <span class="tag orange">Nginx + PHP-FPM</span>
            <span class="tag orange">Multi-stage Dockerfile</span>
            <span class="tag orange">Non-root Container</span>
            <span class="tag orange">Health Checks</span>
            <span class="tag orange">HPA</span>
            <span class="tag orange">PodDisruptionBudget</span>
        </div>
        <div class="card">
            <h3>⚙️ CI/CD & DevOps</h3>
            <span class="tag blue">GitHub Actions</span>
            <span class="tag blue">Self-hosted Runner</span>
            <span class="tag blue">Docker Hub</span>
            <span class="tag blue">Helm v3</span>
            <span class="tag blue">Trivy Security Scan</span>
            <span class="tag blue">Cloudflare Tunnel</span>
            <span class="tag blue">DuckDNS</span>
        </div>
        <div class="card">
            <h3>📊 Observability</h3>
            <span class="tag purple">Prometheus</span>
            <span class="tag purple">Grafana</span>
            <span class="tag purple">kube-prometheus-stack</span>
            <span class="tag purple">NetworkPolicy</span>
            <span class="tag purple">SecurityContext</span>
            <span class="tag purple">Resource Limits</span>
        </div>
    </div>

    <!-- Architecture -->
    <div class="section">
        <div class="section-title">🏛️ Architecture</div>
        <div class="arch">
<span class="highlight">Developer</span> → git push → <span class="green">GitHub</span> → <span class="blue">GitHub Actions</span>
                                                    ↓
                                          <span class="green">Lint + Build + Trivy Scan</span>
                                                    ↓
                                          <span class="blue">Docker Hub</span> (mostafiz01/laravel-k8s)
                                                    ↓
                                    PR merge "Deploy to prod"
                                                    ↓
                                    <span class="highlight">Self-hosted Runner</span> → Helm Deploy
                                                    ↓
┌─────────────────────────────────────────────────────────┐
│                  <span class="green">Kubernetes Cluster</span>                       │
│                                                         │
│   k8s-cp1 (10.216.25.161)  k8s-cp2 (10.216.25.60)     │
│              ↓ MetalLB (10.216.25.200)                  │
│              ↓ ingress-nginx                            │
│              ↓ laravel-app (2 replicas)                 │
│              ↓ HPA (2-5 replicas)                       │
└─────────────────────────────────────────────────────────┘
                    ↓ Cloudflare Tunnel
              <span class="highlight">Public Internet</span> ✅
        </div>
    </div>

    <!-- CI/CD Flow -->
    <div class="section">
        <div class="section-title">🔄 CI/CD Pipeline Flow</div>
        <div class="timeline">
            <div class="timeline-item">
                <h4>Push to develop (any commit)</h4>
                <p>Triggers: Helm lint → Docker build → Push to Docker Hub → Trivy security scan</p>
            </div>
            <div class="timeline-item">
                <h4>Push with "Deploy to dev" in commit message</h4>
                <p>Triggers: All above + extended code quality checks</p>
            </div>
            <div class="timeline-item">
                <h4>Create PR: develop → main</h4>
                <p>Review phase — no automatic deployment triggered</p>
            </div>
            <div class="timeline-item">
                <h4>Merge PR with title "Deploy to prod"</h4>
                <p>Triggers: Helm lint → Helm upgrade on self-hosted runner → kubectl rollout status verify</p>
            </div>
        </div>
    </div>

    <!-- Challenges -->
    <div class="section">
        <div class="section-title">⚡ Challenges Faced & Solutions</div>

        <div class="challenge">
            <h4>KVM modules lost on every reboot</h4>
            <p>Multipass requires KVM but modules were unloaded after restart.</p>
            <div class="fix">✅ Fix: Added modprobe to systemd service ExecStartPre + /etc/modules-load.d/kvm.conf</div>
        </div>

        <div class="challenge">
            <h4>iptables NAT rules not persisting</h4>
            <p>VMs had no internet access after reboot — MASQUERADE rule was lost.</p>
            <div class="fix">✅ Fix: iptables-legacy-save → /etc/iptables/rules.v4 + netfilter-persistent + added to systemd service</div>
        </div>

        <div class="challenge">
            <h4>Helm "invalid ownership metadata" error</h4>
            <p>Resources were created manually before Helm — Helm couldn't take ownership.</p>
            <div class="fix">✅ Fix: kubectl annotate all resources with meta.helm.sh/release-name and managed-by=Helm</div>
        </div>

        <div class="challenge">
            <h4>308 Permanent Redirect on all HTTP requests</h4>
            <p>ingress-nginx force-ssl-redirect was hardcoded in template — caused redirect loops.</p>
            <div class="fix">✅ Fix: Made ssl-redirect configurable via values.yaml + added catch-all ingress rule</div>
        </div>

        <div class="challenge">
            <h4>Multipass VMs show Running but SSH fails</h4>
            <p>VMs started but networking wasn't ready — kubectl couldn't reach API server.</p>
            <div class="fix">✅ Fix: Force stop --all then restart with 60s sleep in systemd service</div>
        </div>

        <div class="challenge">
            <h4>No public URL (moving between ISPs)</h4>
            <p>Public IP changes with every ISP/location change — DuckDNS alone wasn't enough.</p>
            <div class="fix">✅ Fix: Cloudflare Tunnel (cloudflared) with auto-start via cron @reboot</div>
        </div>

        <div class="challenge">
            <h4>GitHub Actions runner needed open terminal</h4>
            <p>Running ./run.sh directly meant closing terminal stopped CI/CD.</p>
            <div class="fix">✅ Fix: Installed as systemd service via ./svc.sh install — now permanent</div>
        </div>

        <div class="challenge">
            <h4>containerd version incompatibility</h4>
            <p>Ubuntu's default containerd package incompatible with kubeadm 1.30.</p>
            <div class="fix">✅ Fix: Install containerd.io from Docker's official repo instead</div>
        </div>
    </div>

    <!-- What Could Be Better -->
    <div class="section">
        <div class="section-title">🎯 What Could Be Better</div>
        <div class="grid">
            <div class="improvement">
                <h4>External PostgreSQL Database</h4>
                <p>Currently using SESSION_DRIVER=file with no DB. Production needs PostgreSQL with migrations.</p>
            </div>
            <div class="improvement">
                <h4>Redis for Session & Cache</h4>
                <p>File-based sessions don't scale across pods. Redis would enable shared sessions.</p>
            </div>
            <div class="improvement">
                <h4>Permanent Public URL</h4>
                <p>Cloudflare tunnel URL changes on restart. A $1/year domain + named tunnel would fix this forever.</p>
            </div>
            <div class="improvement">
                <h4>ArgoCD GitOps</h4>
                <p>Replace Helm CLI deploy with ArgoCD for declarative, auditable deployments.</p>
            </div>
            <div class="improvement">
                <h4>Vault / Sealed Secrets</h4>
                <p>APP_KEY via GitHub Secrets works but Vault or SOPS would be more production-grade.</p>
            </div>
            <div class="improvement">
                <h4>Terraform + Ansible</h4>
                <p>VM provisioning and node setup is manual. IaC would make it fully reproducible.</p>
            </div>
            <div class="improvement">
                <h4>Let's Encrypt TLS</h4>
                <p>cert-manager is installed but uses self-signed certs. Let's Encrypt needs a public domain.</p>
            </div>
            <div class="improvement">
                <h4>SonarQube / DAST</h4>
                <p>Only Trivy image scan in CI. SonarQube for code quality and OWASP ZAP for DAST would improve security.</p>
            </div>
        </div>
    </div>

</div>

<div class="footer">
    <p>Laravel Kubernetes Deployment — Built by <strong>Mostafiz</strong> |
    <a href="https://github.com/Mostafiz-dvps/laravel-k8s-deployment" target="_blank">GitHub Repo</a> |
    PHP {{ PHP_VERSION }} | Laravel {{ app()->version() }}</p>
</div>

</body>
</html>
