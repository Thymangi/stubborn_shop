<?php
// src/Entity/Product.php
namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\OrderItem;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    // NOT NULL + valeur par défaut côté PHP + défaut SQL
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $highlighted = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // JSON NOT NULL + défaut côté PHP
    #[ORM\Column(type: 'json', options: ['default' => '[]'])]
    private array $stocks = ['S'=>0,'M'=>0,'L'=>0,'XL'=>0];

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'product')]
    private Collection $orderItems;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();

        // Sécurise les clés si l’objet est désérialisé/chargé différemment
        foreach (['S','M','L','XL'] as $k) {
            if (!array_key_exists($k, $this->stocks)) {
                $this->stocks[$k] = 0;
            }
        }
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getPrice(): ?float { return $this->price; }
    public function setPrice(float $price): static { $this->price = $price; return $this; }

    public function getImage(): ?string
    {
        $img = $this->image;

        if (!$img) return null;

        // Si un tableau est stocké (cas migration)
        if (is_array($img)) {
            $img = $img[0] ?? null;
        } elseif (is_string($img)) {
            $decoded = json_decode($img, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $img = $decoded[0] ?? null;
            }
        }

        if (!is_string($img) || $img === '') return null;

        // retire "public/" si présent
        if (str_starts_with($img, 'public/')) {
            $img = substr($img, 7);
        }

        // normalise le slash de tête
        $img = ltrim($img, '/');

        // si pas déjà "uploads/" ni URL absolue, préfixe
        if (!preg_match('#^(uploads/|https?://)#i', $img)) {
            $img = 'uploads/'.$img;
        }

        // évite uploads/uploads/...
        $img = preg_replace('#^uploads/+uploads/+?#i', 'uploads/', $img);

        return $img;
    }

    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function isHighlighted(): bool { return $this->highlighted; }
    public function setHighlighted(bool $highlighted): static { $this->highlighted = $highlighted; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    /** @return array{S?:int,M?:int,L?:int,XL?:int} */
    public function getStocks(): array { return $this->stocks; }
    public function setStocks(array $stocks): static
    {
        // merge pour garantir les 4 clés
        $defaults = ['S'=>0,'M'=>0,'L'=>0,'XL'=>0];
        $this->stocks = array_merge($defaults, array_map('intval', $stocks));
        return $this;
    }

    public function getStockForSize(string $size): int
    {
        return (int) ($this->stocks[$size] ?? 0);
    }

    public function setStockForSize(string $size, int $qty): static
    {
        $qty = max(0, (int)$qty);
        $stocks = $this->stocks;    // copie
        $stocks[$size] = $qty;
        $this->stocks = $stocks;    // réassignation => Doctrine détecte le changement
        return $this;
    }

    // Helpers lecture
    public function getSizeS(): int { return $this->getStockForSize('S'); }
    public function getSizeM(): int { return $this->getStockForSize('M'); }
    public function getSizeL(): int { return $this->getStockForSize('L'); }
    public function getSizeXl(): int { return $this->getStockForSize('XL'); }

    // Helpers écriture (compat éventuelle)
    public function setSizeS(int $q): static { return $this->setStockForSize('S', $q); }
    public function setSizeM(int $q): static { return $this->setStockForSize('M', $q); }
    public function setSizeL(int $q): static { return $this->setStockForSize('L', $q); }
    public function setSizeXl(int $q): static { return $this->setStockForSize('XL', $q); }

    /** Total de stock toutes tailles confondues. */
    public function getTotalStock(): int
    {
        $sum = 0;
        foreach ($this->stocks as $qty) {
            $sum += (int)$qty;
        }
        return $sum;
    }

    /** @return Collection<int, OrderItem> */
    public function getOrderItems(): Collection { return $this->orderItems; }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setProduct($this);
        }
        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            if ($orderItem->getProduct() === $this) {
                $orderItem->setProduct(null);
            }
        }
        return $this;
    }
}