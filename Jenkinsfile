pipeline {
    agent any
    stages {
        stage("Build") {

            steps {
                sh 'php --version'
                sh 'composer install'
                sh 'composer --version'
                sh 'mv .env.example .env'
                sh 'php artisan key:generate'
                echo "Build completed"
            }
        }
        
    }
}
