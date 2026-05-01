# Laravel Kubernetes Deployment

A production-ready Laravel application deployed on a kubeadm Kubernetes cluster using Docker and Helm.

## Live Demo
- **Public URL:** https://planning-contains-suite-clear.trycloudflare.com
- **Health Check:** https://planning-contains-suite-clear.trycloudflare.com/health

> Note: URL is served via Cloudflare Tunnel and may change on host restart.


## Repository Structure
laravel-k8s-deployment/
├── app/                          # Laravel application
│   ├── Dockerfile                # Multi-stage production Dockerfile
│   ├── .dockerignore
│   ├── docker/                   # Nginx, PHP-FPM, Supervisor configs
│   └── routes/web.php            # App routes including /health
├── helm/laravel/                 # Helm chart
│   ├── Chart.yaml
│   ├── values.yaml
│   └── templates/
│       ├── deployment.yaml
│       ├── service.yaml
│       ├── ingress.yaml
│       ├── configmap.yaml
│       ├── secret.yaml
│       ├── pvc.yaml
│       ├── namespace.yaml
│       ├── hpa.yaml
│       ├── pdb.yaml
│       ├── networkpolicy.yaml
│       └── serviceaccount.yaml
├── .github/
│   ├── actions/setvars/          # Reusable env vars action
│   ├── variables/laravel.env     # Pipeline variables (non-sensitive)
│   └── workflows/
│       ├── deploy-develop.yaml         # Push to develop → lint + build
│       ├── deploy-main.yaml            # PR merge to main → build + deploy
│       ├── callable-docker-push.yaml   # Reusable build + push + trivy
│       └── callable-helm-deploy.yaml   # Reusable helm deploy
├── docs/screenshots/             # Cluster verification screenshots
└── README.md
## Docker Image

- **Repository:** `mostafiz01/laravel-k8s`
- **Tag:** `1.0.0`
- **Full URL:** `docker.io/mostafiz01/laravel-k8s:1.0.0`
- **Pull command:** `docker pull mostafiz01/laravel-k8s:1.0.0`


## CI/CD Pipeline Notes

### Dev Pipeline (develop branch)
Lint and Docker build+push work fully automated via GitHub Actions.
Trivy security scan runs after every successful build.

### Production Pipeline (PR merge to main)
Docker build, push and helm deploy are fully automated via GitHub Actions.
A self-hosted runner runs on the host machine with direct kubeconfig access to the cluster.













## Architecture Overview
Developer → GitHub → GitHub Actions → Docker Hub → Kubernetes Cluster
│
┌──────────┴──────────┐
│                     │
k8s-cp1              k8s-cp2
(control-plane)      (control-plane)
│
k8s-worker1
(worker)
│
ingress-nginx
│
laravel-app
(2 replicas)
## Cluster Details

| Node | Role | IP | Version |
|---|---|---|---|
| k8s-cp1 | control-plane | 10.216.25.161 | v1.30.14 |
| k8s-cp2 | control-plane | 10.216.25.60 | v1.30.14 |
| k8s-worker1 | worker | 10.216.25.185 | v1.30.14 |

- **CNI:** Calico v3.27.0
- **Ingress:** ingress-nginx v1.10.0
- **CRI:** containerd 2.2.3
- **StorageClass:** local-path-provisioner v0.0.26
- **LoadBalancer:** MetalLB v0.14.3 (IP pool: 10.216.25.200-220)
- **TLS:** cert-manager v1.14.0 (self-signed ClusterIssuer)
- **Monitoring:** kube-prometheus-stack (Grafana + Prometheus)

## Prerequisites

- Ubuntu 24.04 host machine
- Multipass installed (`sudo snap install multipass`)
- KVM enabled (`sudo modprobe kvm && sudo modprobe kvm_intel`)
- Docker installed
- Helm v3 installed
- kubectl installed

## Cluster Setup Steps

### 1. Launch VMs

```bash
multipass launch --name k8s-cp1 --cpus 2 --memory 3G --disk 20G 24.04
multipass launch --name k8s-cp2 --cpus 2 --memory 3G --disk 20G 24.04
multipass launch --name k8s-worker1 --cpus 2 --memory 4G --disk 20G 24.04
```

### 2. Setup each node (run on all 3)

```bash
sudo swapoff -a
sudo modprobe overlay && sudo modprobe br_netfilter

# Install containerd from Docker repo
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
echo "deb [arch=amd64 signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu noble stable" | sudo tee /etc/apt/sources.list.d/docker.list
sudo apt-get update && sudo apt-get install -y containerd.io
sudo mkdir -p /etc/containerd
containerd config default | sudo tee /etc/containerd/config.toml
sudo sed -i 's/SystemdCgroup = false/SystemdCgroup = true/' /etc/containerd/config.toml
sudo systemctl restart containerd

# Install kubeadm kubelet kubectl
curl -fsSL https://pkgs.k8s.io/core:/stable:/v1.30/deb/Release.key | sudo gpg --dearmor -o /etc/apt/keyrings/kubernetes-apt-keyring.gpg
echo 'deb [signed-by=/etc/apt/keyrings/kubernetes-apt-keyring.gpg] https://pkgs.k8s.io/core:/stable:/v1.30/deb/ /' | sudo tee /etc/apt/sources.list.d/kubernetes.list
sudo apt-get update && sudo apt-get install -y kubelet kubeadm kubectl
sudo apt-mark hold kubelet kubeadm kubectl
```

### 3. Initialize control plane (cp1 only)

```bash
sudo kubeadm init \
  --control-plane-endpoint "10.216.25.161:6443" \
  --upload-certs \
  --pod-network-cidr=192.168.0.0/16

mkdir -p $HOME/.kube
sudo cp /etc/kubernetes/admin.conf $HOME/.kube/config
kubectl apply -f https://raw.githubusercontent.com/projectcalico/calico/v3.27.0/manifests/calico.yaml
```

### 4. Join cp2 (control-plane)

```bash
sudo kubeadm join 10.216.25.161:6443 \
  --token <token> \
  --discovery-token-ca-cert-hash sha256:<hash> \
  --control-plane \
  --certificate-key <cert-key>
```

### 5. Join worker1

```bash
sudo kubeadm join 10.216.25.161:6443 \
  --token <token> \
  --discovery-token-ca-cert-hash sha256:<hash>
```

### 6. Install ingress-nginx

```bash
kubectl apply -f https://raw.githubusercontent.com/kubernetes/ingress-nginx/controller-v1.10.0/deploy/static/provider/baremetal/deploy.yaml
```

### 7. Install StorageClass

```bash
kubectl apply -f https://raw.githubusercontent.com/rancher/local-path-provisioner/v0.0.26/deploy/local-path-storage.yaml
kubectl patch storageclass local-path -p '{"metadata": {"annotations":{"storageclass.kubernetes.io/is-default-class":"true"}}}'
```

## Docker Build & Push

```bash
# Build
docker build -t mostafiz01/laravel-k8s:1.0.0 ./app

# Test locally
docker run -d --name laravel-test \
  -p 8080:8080 \
  -e APP_KEY="<your-app-key>" \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e SESSION_DRIVER=file \
  mostafiz01/laravel-k8s:1.0.0

curl http://localhost:8080/
curl http://localhost:8080/health

# Push
docker push mostafiz01/laravel-k8s:1.0.0
docker push mostafiz01/laravel-k8s:latest
```

**Image:** `mostafiz01/laravel-k8s:1.0.0`

## Helm Install / Upgrade / Uninstall

### Generate APP_KEY

```bash
cd app && php artisan key:generate --show
```

### Install

```bash
helm install laravel ./helm/laravel \
  --set secret.appKey="<APP_KEY>"
```

### Upgrade

```bash
helm upgrade laravel ./helm/laravel \
  --set secret.appKey="<APP_KEY>" \
  --set image.tag="<NEW_TAG>"
```

### Uninstall

```bash
helm uninstall laravel -n laravel
```

## Testing

### Option 0 — MetalLB LoadBalancer (Direct)

```bash
```

### Option 1 — curl with Host header

```bash
curl -H "Host: laravel-test.local" http://10.216.25.200/
curl -H "Host: laravel-test.local" http://10.216.25.200/health
```

### Option 2 — /etc/hosts

```bash
echo "10.216.25.185 laravel-test.local" | sudo tee -a /etc/hosts
curl http://10.216.25.200/
curl http://10.216.25.200/health
```

Expected responses:
- `/` → `Laravel Kubernetes Deployment Test`
- `/health` → `OK` (HTTP 200)

## CI/CD Pipeline

### Structure
.github/workflows/
├── deploy-develop.yaml          # triggers on push to develop
│   ├── helm lint
│   └── calls callable-docker-push
├── deploy-main.yaml             # triggers on PR merge to main
│   ├── calls callable-docker-push
│   └── calls callable-helm-deploy
├── callable-docker-push.yaml    # reusable build+push+scan
└── callable-helm-deploy.yaml    # reusable helm deploy

### Flow
push to develop → lint + build + push + Trivy scan
PR merge to main → build + push + helm deploy to production
### Required GitHub Secrets

| Secret | Description |
|---|---|
| `DOCKERHUB_USERNAME` | Docker Hub username |
| `DOCKERHUB_TOKEN` | Docker Hub PAT |
| `APP_KEY` | Laravel APP_KEY |

## Secret Management

APP_KEY is passed via `--set` at helm install time and stored in a
Kubernetes Secret. It is never committed to git or hardcoded in any file.

### Why not Azure Key Vault or GitHub Secrets directly?
- **GitHub Secrets** are CI/CD pipeline variables — not accessible by running pods.
  They are used correctly here to pass APP_KEY into helm which stores it in a K8s Secret.
- **Azure Key Vault** requires Azure AD identity and CSI driver — not available on
  bare kubeadm without significant additional infrastructure.

### Production approach
Use Azure Key Vault with CSI driver and Managed Identity (AKS),
or Sealed Secrets / SOPS for GitOps-safe secret storage.

## Laravel Runtime Commands

| Command | When | How |
|---|---|---|
| `config:cache` | Every deploy | initContainer |
| `route:cache` | Every deploy | initContainer |
| `storage:link` | Every deploy | initContainer |
| `migrate` | Manual only | Never auto-run in production — data loss risk |
| `key:generate` | Once | Run locally, store in Secret |

## Troubleshooting

### containerd not found after install
Ubuntu's default containerd package (2.2.x) is incompatible with kubeadm 1.30.
**Fix:** Install `containerd.io` from Docker's official repo instead. 

### KVM not available after reboot
KVM modules are not loaded by default after reboot.
**Fix:**
```bash
sudo modprobe kvm && sudo modprobe kvm_intel
# Make permanent:
echo -e "kvm\nkvm_intel" | sudo tee /etc/modules-load.d/kvm.conf
```

### VMs have no internet access
Multipass VMs can't reach internet — NAT rules missing.
**Fix:**
```bash
sudo iptables-legacy -t nat -A POSTROUTING -s 10.216.25.0/24 -o wlp0s20f3 -j MASQUERADE
sudo iptables-legacy -A FORWARD -s 10.216.25.0/24 -j ACCEPT
sudo netfilter-persistent save
```

### Pods not ready — session file error
Laravel tries to write session files to storage directory which is
overwritten by PVC mount.
**Fix:** initContainer creates directory structure before app starts:
```bash
mkdir -p /var/www/html/storage/framework/sessions \
  /var/www/html/storage/framework/views \
  /var/www/html/storage/framework/cache
```

### PVC pending
No default StorageClass on bare kubeadm.
**Fix:** Install local-path-provisioner and set as default.

## Assumptions

- Single namespace (`laravel`) for all application resources
- SQLite not used — SESSION_DRIVER=file, no external database for this demo
- local-path StorageClass used for PVC — data is node-local, not replicated
- ingress-nginx uses MetalLB LoadBalancer (IP: 10.216.25.200)
- APP_KEY passed at install time, not stored in repo

## Production Improvement Suggestions

### CI/CD
- Migrate to GitLab CI/CD for self-hosted runner support
- Add SonarQube for static code analysis on every PR
- Add OWASP dependency check for CVE scanning
- Implement branch protection with required reviews before merge

### Infrastructure
- Use Terraform to provision VMs and Kubernetes cluster
- Use Ansible for node configuration instead of manual kubeadm setup
- cert-manager already installed with self-signed issuer — upgrade to Let's Encrypt for production
- Implement WAF (ModSecurity/NAXSI) at ingress level

### Kubernetes
- External PostgreSQL database instead of SQLite
- Redis for session and cache management
- Horizontal Pod Autoscaler already included
- Add Vertical Pod Autoscaler for right-sizing
- Implement blue-green deployments via ingress weight splitting

### Security
- Azure Key Vault with CSI driver for secret management (AKS)
- Sealed Secrets or SOPS for GitOps-safe secret storage
- Network policies already included — extend to restrict egress per service
- Pod Security Admission for namespace-level security standards
- Regular image vulnerability scanning with Trivy in CI

### Monitoring
- Prometheus + Grafana already deployed via kube-prometheus-stack
- Add Sentry for application error tracking
- Implement centralized log aggregation (ELK or Graylog)
- Set up uptime monitoring and SLA dashboards
