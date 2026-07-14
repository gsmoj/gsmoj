# GitHub Actions 및 배포 지침

이 디렉터리의 배포 워크플로는 `.github/workflows/deploy.yml`이다.

## 현재 배포 흐름

- `main` 브랜치 push 시 GitHub Actions가 실행된다.
- `appleboy/ssh-action@v1.0.3`으로 서버에 접속한다.
- 운영 서버의 `/home/judge/src/web`에서 `git pull origin main`을 실행한다.
- 따라서 `main` push는 곧 운영 반영이다.

## 작업 규칙

- GitHub Secrets 이름 `SERVER_HOST`, `SERVER_USER`, `SERVER_KEY`를 바꾸지 않는다.
- SSH 포트 `27117`을 변경할 때는 서버 설정 변경 여부를 먼저 확인한다.
- step 이름과 주석은 UTF-8 한국어로 정리한다.
- 워크플로 수정 후 YAML 들여쓰기, Secrets 이름, SSH 접속 경로와 권한을 확인한다.
- 현재 워크플로에는 테스트 단계가 없으므로 push 전에 로컬 또는 별도 환경에서 검증한다.
- DB 마이그레이션, 권한 변경, 서버 패키지 설치, 파일 권한 변경은 자동 배포만으로 끝내지 말고 별도 수동 절차를 문서화한다.
- GitHub Actions 실패 시 SSH 접속, 경로, 권한, `git pull` 충돌 여부를 Actions 로그에서 먼저 확인한다.
- 서버 직접 수정은 `git pull`을 실패시키거나 변경을 덮을 수 있으므로 피한다.
