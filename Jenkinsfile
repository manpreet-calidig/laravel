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
        
        stage("Package Build") {
            steps {    
                sh "tar -zcvf bundle.tar.gz *"
            }
        }
        
        stage("Artifacts Creation") {
            steps {    
				fingerprint 'bundle.tar.gz'
				archiveArtifacts 'bundle.tar.gz'
				echo "Artifacts created"
            }
        }
        
        stage("Stash changes") {
            steps {    
                stash allowEmpty: true, includes: 'bundle.tar.gz', name: 'buildArtifacts'
            }
        }
        
        
    }
}
