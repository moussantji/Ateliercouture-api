<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Service;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'Couture Atelier',
            'email' => 'contact@coutureatelier.com',
            'phone' => '+223 XX XX XX XX',
            'password' => bcrypt('password'),
        ]);

        // Services — icônes Ionicons
        $services = [
            ['name' => 'Robe sur mesure', 'slug' => 'robe-sur-mesure', 'duration' => '3-5 jours', 'price' => 'À partir de 35 000 FCFA', 'icon' => 'shirt-outline', 'color' => '#E86868', 'category' => 'robe', 'description' => 'Robes de cérémonie, mariage, soirée', 'sort_order' => 1],
            ['name' => 'Costume sur mesure', 'slug' => 'costume-sur-mesure', 'duration' => '5-7 jours', 'price' => 'À partir de 65 000 FCFA', 'icon' => 'man-outline', 'color' => '#5BA0E0', 'category' => 'costume', 'description' => 'Costumes 2 et 3 pièces', 'sort_order' => 2],
            ['name' => 'Boubou traditionnel', 'slug' => 'boubou-traditionnel', 'duration' => '2-3 jours', 'price' => 'À partir de 20 000 FCFA', 'icon' => 'glasses-outline', 'color' => '#CFA866', 'category' => 'boubou', 'description' => 'Bazin riche, broderie main', 'sort_order' => 3],
            ['name' => 'Retouches & Ajustements', 'slug' => 'retouches-ajustements', 'duration' => '1 jour', 'price' => 'À partir de 5 000 FCFA', 'icon' => 'cut-outline', 'color' => '#A87DD8', 'category' => 'retouche', 'description' => 'Ourlets, reprises, modifications', 'sort_order' => 4],
            ['name' => 'Consultation stylisme', 'slug' => 'consultation-stylisme', 'duration' => '1h', 'price' => 'Gratuit', 'icon' => 'chatbubbles-outline', 'color' => '#5CB87A', 'category' => 'consultation', 'description' => 'Conseils en style et tissus', 'sort_order' => 5],
            ['name' => 'Accessoires personnalisés', 'slug' => 'accessoires-personnalises', 'duration' => '2-4 jours', 'price' => 'À partir de 8 000 FCFA', 'icon' => 'bag-handle-outline', 'color' => '#E8A23A', 'category' => 'accessoire', 'description' => 'Sacs, ceintures, pagnes', 'sort_order' => 6],
        ];
        foreach ($services as $svc) {
            Service::create($svc);
        }

        // Products
        $products = [
            ['name' => 'Robe de Cérémonie', 'slug' => 'robe-de-ceremonie', 'category' => 'Robes', 'price' => '45 000 FCFA', 'original_price' => '55 000 FCFA', 'badge' => '-18%', 'badge_type' => 'sale', 'gradient_colors' => '["#8B2020","#E86868"]', 'emoji' => 'shirt-outline', 'rating' => 4.9, 'description' => 'Tissu bazin riche, broderie main', 'sort_order' => 1],
            ['name' => 'Costume 3 Pièces', 'slug' => 'costume-3-pieces', 'category' => 'Costumes', 'price' => '85 000 FCFA', 'original_price' => null, 'badge' => 'Nouveau', 'badge_type' => 'new', 'gradient_colors' => '["#0F2038","#3A5A80"]', 'emoji' => 'man-outline', 'rating' => 4.8, 'description' => 'Coupe moderne, sur-mesure', 'sort_order' => 2],
            ['name' => 'Boubou Tradition', 'slug' => 'boubou-tradition', 'category' => 'Boubous', 'price' => '25 000 FCFA', 'original_price' => '30 000 FCFA', 'badge' => '-17%', 'badge_type' => 'sale', 'gradient_colors' => '["#CFA866","#E8C880"]', 'emoji' => 'glasses-outline', 'rating' => 4.9, 'description' => 'Bazin riche, broderie traditionnelle', 'sort_order' => 3],
            ['name' => 'Robe de Soirée', 'slug' => 'robe-de-soiree', 'category' => 'Robes', 'price' => '65 000 FCFA', 'original_price' => null, 'badge' => '⭐ Tendance', 'badge_type' => 'trend', 'gradient_colors' => '["#4B0082","#9370DB"]', 'emoji' => 'shirt-outline', 'rating' => 5.0, 'description' => 'Soie & dentelle de luxe', 'sort_order' => 4],
            ['name' => 'Chemise Sur-Mesure', 'slug' => 'chemise-sur-mesure', 'category' => 'Costumes', 'price' => '18 000 FCFA', 'original_price' => null, 'badge' => 'Populaire', 'badge_type' => 'popular', 'gradient_colors' => '["#5BA0E0","#87CEEB"]', 'emoji' => 'man-outline', 'rating' => 4.7, 'description' => 'Tissus premium, coupe ajustée', 'sort_order' => 5],
            ['name' => 'Sac Tissé Main', 'slug' => 'sac-tisse-main', 'category' => 'Accessoires', 'price' => '8 500 FCFA', 'original_price' => null, 'badge' => null, 'badge_type' => null, 'gradient_colors' => '["#8B4513","#DEB887"]', 'emoji' => 'bag-handle-outline', 'rating' => 4.6, 'description' => 'Artisanat local, cuir & tissu', 'sort_order' => 6],
            ['name' => 'Boubou Élégance', 'slug' => 'boubou-elegance', 'category' => 'Boubous', 'price' => '38 000 FCFA', 'original_price' => '42 000 FCFA', 'badge' => '-10%', 'badge_type' => 'sale', 'gradient_colors' => '["#2E6B45","#5CB87A"]', 'emoji' => 'glasses-outline', 'rating' => 4.9, 'description' => 'Collection premium bazin', 'sort_order' => 7],
            ['name' => 'Pagne Bazin', 'slug' => 'pagne-bazin', 'category' => 'Accessoires', 'price' => '15 000 FCFA', 'original_price' => null, 'badge' => null, 'badge_type' => null, 'gradient_colors' => '["#E86868","#FFA07A"]', 'emoji' => 'bag-handle-outline', 'rating' => 4.5, 'description' => 'Tissu bazin riche, motif wax', 'sort_order' => 8],
            ['name' => 'Robe Traditionnelle', 'slug' => 'robe-traditionnelle', 'category' => 'Robes', 'price' => '55 000 FCFA', 'original_price' => null, 'badge' => 'Collection', 'badge_type' => 'new', 'gradient_colors' => '["#A87DD8","#DA70D6"]', 'emoji' => 'shirt-outline', 'rating' => 4.8, 'description' => 'Coupe traditionnelle moderne', 'sort_order' => 9],
            ['name' => 'Costume Cérémonie', 'slug' => 'costume-ceremonie', 'category' => 'Costumes', 'price' => '95 000 FCFA', 'original_price' => '110 000 FCFA', 'badge' => '-14%', 'badge_type' => 'sale', 'gradient_colors' => '["#2F4F4F","#708090"]', 'emoji' => 'man-outline', 'rating' => 5.0, 'description' => 'Tissu laine & soie haut de gamme', 'sort_order' => 10],
        ];
        foreach ($products as $prod) {
            Product::create($prod);
        }

        // Reviews
        $reviews = [
            ['name' => 'Aminata Diallo', 'text' => "Un travail d'orfèvre ! Ma robe de mariée était un rêve éveillé. Les broderies sont magnifiques.", 'rating' => 5, 'color' => '#E86868'],
            ['name' => 'Mamadou Keita', 'text' => 'Costume parfaitement ajusté, tissu de grande qualité. Le sur-mesure fait toute la différence.', 'rating' => 5, 'color' => '#5BA0E0'],
            ['name' => 'Fatou Sow', 'text' => 'Rapidité, professionnalisme et un sens du détail exceptionnel. Mon boubou était prêt en 2 jours !', 'rating' => 5, 'color' => '#CFA866'],
            ['name' => 'Oumar Traoré', 'text' => "Service de retouche impeccable. L'équipe est très à l'écoute et professionnelle.", 'rating' => 4, 'color' => '#5CB87A'],
        ];
        foreach ($reviews as $rev) {
            Review::create($rev);
        }

        echo "✅ Base de données initialisée avec succès !\n";
    }
}
