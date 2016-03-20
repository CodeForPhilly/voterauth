# voterauth
A hosted authentication service based on the Philadelphia voter file for applications that want to verify voters with medium-security

## Usage
```bash
curl -X POST 'http://voterauth.phl.io/oauth2/token' -d 'grant_type=voter&date_of_birth=1976-02-20&house_number=1234'
```

## Roadmap
- [ ] Create a workflow for registering apps
  - [ ] Issue custom secret for each app
  - [ ] Require app be specified for token requests and use app-specific secret to sign JWT
- [ ] Add additional `grant_type`s that offer stricter verification
