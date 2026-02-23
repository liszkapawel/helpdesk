import { execSync } from 'child_process'

export default async function globalSetup() {
  console.log('Loading fixtures...')
  execSync(
    'docker compose exec -T php bin/console doctrine:fixtures:load --no-interaction',
    {
      cwd: '..',
      stdio: 'inherit',
    }
  )
  console.log('Fixtures loaded.')
}
