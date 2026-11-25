<?php

namespace Database\Seeders;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        (new CategoryFactory)->create(['name' => 'Eletrônicos', 'description' => 'Produtos relacionados à tecnologia, como smartphones, laptops, tablets, TVs, acessórios como fones de ouvido, carregadores e dispositivos inteligentes.']);
        (new CategoryFactory)->create(['name' => 'Roupas e Acessórios', 'description' => 'Vestuário para homens, mulheres e crianças, incluindo camisetas, calças, vestidos, calçados, bolsas, relógios, óculos de sol e outros acessórios de moda.']);
        (new CategoryFactory)->create(['name' => 'Beleza e Cuidados Pessoais', 'description' => 'Cosméticos, maquiagens, produtos para o cuidado da pele, cabelo e higiene pessoal, como perfumes, hidratantes, shampoos e aparelhos de barbear.']);
        (new CategoryFactory)->create(['name' => 'Casa e Decoração', 'description' => 'Móveis, artigos de decoração, itens para cozinha, organização, iluminação, roupas de cama, mesa e banho.']);
        (new CategoryFactory)->create(['name' => 'Esportes e Lazer', 'description' => 'Equipamentos esportivos, roupas fitness, acessórios para práticas ao ar livre (como camping), bicicletas e produtos de bem-estar.']);
        (new CategoryFactory)->create(['name' => 'Livros, Filmes e Música', 'description' => 'Livros físicos e digitais, DVDs, Blu-rays, vinis, instrumentos musicais e outros materiais relacionados ao entretenimento.']);
        (new CategoryFactory)->create(['name' => 'Brinquedos e Jogos', 'description' => 'Brinquedos infantis, jogos de tabuleiro, quebra-cabeças, videogames e acessórios para consoles.']);
        (new CategoryFactory)->create(['name' => 'Alimentos e Bebidas', 'description' => 'Produtos alimentícios, como itens gourmet, snacks, alimentos saudáveis, bebidas alcoólicas e não alcoólicas.']);
        (new CategoryFactory)->create(['name' => 'Automotivo', 'description' => 'Peças de reposição, acessórios para veículos, como capas de banco, tapetes, produtos para manutenção e limpeza automotiva.']);
        (new CategoryFactory)->create(['name' => 'Saúde e Bem-estar', 'description' => 'Produtos como suplementos alimentares, vitaminas, itens de primeiros socorros, equipamentos médicos e de fisioterapia.']);
    }
}
