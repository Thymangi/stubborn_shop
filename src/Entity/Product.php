<?php
// src/Entity/Product.php
namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\OrderItem; // ✅ important

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

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column]
    private ?bool $highlighted = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // JSON: { "S": int, "M": int, "L": int, "XL": int }
    #[ORM\Column(type: 'json')]
    private array $stocks = [];

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'product')]
    private Collection $orderItems;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
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

    if (is_array($img)) {
        $img = $img[0] ?? null;
    } elseif (is_string($img)) {
        $decoded = json_decode($img, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $img = $decoded[0] ?? null;
        }
    }

    if (!is_string($img) || $img === '') return null;

    // remove leading "public/"
    if (str_starts_with($img, 'public/')) {
        $img = substr($img, 7);
    }
    // remove any leading slash
    $img = ltrim($img, '/');

    // If it already starts with "uploads/" or is an absolute URL, keep it
    if (!preg_match('#^(uploads/|https?://)#i', $img)) {
        $img = 'uploads/'.$img;
    }

    // (bonus) collapse a possible duplicate "uploads/uploads/"
    $img = preg_replace('#^uploads/+uploads/+?#i', 'uploads/', $img);

    return $img;
    
}

    public function setImage(string $image): static { $this->image = $image; return $this; }

    public function isHighlighted(): ?bool { return $this->highlighted; }
    public function setHighlighted(bool $highlighted): static { $this->highlighted = $highlighted; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    /** @return array{S?:int,M?:int,L?:int,XL?:int} */
    public function getStocks(): array { return $this->stocks; }
    public function setStocks(array $stocks): static { $this->stocks = $stocks; return $this; }

    public function getStockForSize(string $size): int
    {
        return (int) ($this->stocks[$size] ?? 0);
    }

    public function setStockForSize(string $size, int $qty): static
    {
        $new = $this->stocks;        // copie
        $new[$size] = max(0, $qty);    // modif
        $this->stocks = $new;          // réassignation pour Doctrine 
        return $this;
    }

    public function getSizeS(): int { return $this->getStockForSize('S'); }
    public function getSizeM(): int { return $this->getStockForSize('M'); }
    public function getSizeL(): int { return $this->getStockForSize('L'); }
    public function getSizeXl(): int { return $this->getStockForSize('XL'); }

    /** Total de stock toutes tailles confondues. */
    public function getTotalStock(): int
    {
        if (!is_array($this->stocks) || $this->stocks === []) {
            return $this->getSizeS() + $this->getSizeM() + $this->getSizeL() + $this->getSizeXl();
        }
        $sum = 0;
        foreach ($this->stocks as $qty) {
            $sum += (int) $qty;
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