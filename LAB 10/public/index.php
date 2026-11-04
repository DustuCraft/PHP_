<?php

declare(strict_types=1);

use App\Cart\Cart;
use App\Catalog\Menu;
use App\Delivery\Courier;
use App\Delivery\Pickup;
use App\Enums\Category;
use App\Enums\DeliveryMethod;
use App\Exceptions\AddressException;
use App\Exceptions\DeliveryException;
use App\Services\DeliveryService;
use App\Support\Money;
use App\ValueObject\Address;
use App\ValueObject\Order;
use App\ValueObject\PickupPoint;

// Сессии храним в var/sessions: путь по умолчанию может быть недоступен для записи.
$sessionDir = dirname(__DIR__) . '/var/sessions';

if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0775, true);
}

session_save_path($sessionDir);
session_start();

require dirname(__DIR__) . '/src/bootstrap.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$menu = new Menu();
$cart = new Cart();

// Зависимости передаются снаружи: сервис знает только интерфейс DeliveryCalculator.
$courier = new Courier();
$pickup = new Pickup();
$service = new DeliveryService([$courier, $pickup]);

$form = [
    'city' => (string) ($_POST['city'] ?? 'Алматы'),
    'district' => (string) ($_POST['district'] ?? 'Центр'),
    'street' => (string) ($_POST['street'] ?? 'Абая'),
    'house' => (string) ($_POST['house'] ?? '15'),
    'apartment' => (string) ($_POST['apartment'] ?? ''),
    'pickup' => (string) ($_POST['pickup'] ?? 'PVZ-01'),
];

$method = DeliveryMethod::tryFrom((string) ($_POST['method'] ?? 'courier')) ?? DeliveryMethod::Courier;
$error = null;
$quote = null;
$order = null;
$place = '';
$logs = [];
$finished = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add') {
        $cart->add((string) ($_POST['id'] ?? ''));
    } elseif ($action === 'plus') {
        $cart->increase((string) ($_POST['id'] ?? ''));
    } elseif ($action === 'minus') {
        $cart->decrease((string) ($_POST['id'] ?? ''));
    } elseif ($action === 'remove') {
        $cart->remove((string) ($_POST['id'] ?? ''));
    } elseif ($action === 'clear') {
        $cart->clear();
    }

    if ($action === 'checkout') {
        $address = $method === DeliveryMethod::Courier
            ? new Address(
                city: $form['city'],
                district: $form['district'],
                street: $form['street'],
                house: $form['house'],
                apartment: $form['apartment'],
            )
            : null;

        $point = $method === DeliveryMethod::Pickup
            ? new PickupPoint($form['pickup'], '', '', '')
            : null;

        try {
            $order = new Order(
                items: $cart->lines($menu),
                method: $method,
                address: $address,
                pickupPoint: $point,
            );

            $quote = $service->quote($order);
            $logs = $service->calculatorFor($method)->logEntries();
            $place = $method === DeliveryMethod::Courier
                ? $address->line()
                : (PickupPoint::find($form['pickup'])?->title ?? 'Пункт выдачи');
        } catch (AddressException $e) {
            $error = $e->getMessage();
            $errorHint = 'Проверьте адрес: улица — только буквы, дом — число, например 15 или 15А.';
        } catch (\InvalidArgumentException $e) {
            $error = $e->getMessage();
            $errorHint = 'Добавьте хотя бы одну позицию из меню.';
        } catch (DeliveryException $e) {
            $error = $e->getMessage();
            $errorHint = 'Попробуйте самовывоз или оформите заказ позже.';
            error_log($e->__toString());
        } finally {
            $finished = true;
        }
    } elseif ($action !== '') {
        $quote = null;
    }
}

$groups = $menu->grouped();
$lines = $cart->lines($menu);
$subtotal = $cart->subtotal($menu);
$freeFrom = 8000;
$untilFree = max(0, $freeFrom - $subtotal);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FastDelivery — симулятор доставки · ЛР №10</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<header class="topbar">
    <div class="brand">
        <img class="brand__logo" src="assets/courier-scooter.png" alt="">
        <div>
            <div class="brand__name">FastDelivery</div>
            <div class="brand__tag">Лабораторная работа №10 · вариант 4</div>
        </div>
    </div>
    <nav class="topbar__nav">
        <a href="#menu">Меню</a>
        <a href="#cart">Корзина</a>
        <a href="#result">Доставка</a>
    </nav>
</header>

<section class="hero">
    <div class="hero__text">
        <p class="hero__eyebrow">Симулятор доставки еды</p>
        <h1>Собери заказ — курьер уже рядом</h1>
        <p class="hero__lead">
            Фастфуд, комбо и напитки. Выберите доставку курьером или самовывоз:
            стоимость и время рассчитает сервис через общий интерфейс
            <code>DeliveryCalculator</code>.
        </p>
        <div class="hero__chips">
            <span class="chip">🛵 Курьер от 20 мин</span>
            <span class="chip">🏪 Самовывоз 0 ₸</span>
            <span class="chip">🎁 Бесплатно от <?= Money::format($freeFrom) ?></span>
        </div>
    </div>
    <img class="hero__img" src="assets/courier-scooter.png" alt="Курьер на скутере">
</section>

<main class="layout">
    <section class="menu" id="menu">
        <?php foreach ($groups as $groupKey => $products): ?>
            <?php $category = Category::from($groupKey); ?>
            <div class="menu__section">
                <h2 class="menu__title">
                    <span class="menu__icon"><?= $category->icon() ?></span>
                    <?= e($category->label()) ?>
                    <span class="menu__count"><?= count($products) ?></span>
                </h2>
                <div class="cards">
                    <?php foreach ($products as $product): ?>
                        <article class="card product">
                            <div class="product__top">
                                <h3><?= e($product->title) ?></h3>
                                <span class="product__price"><?= Money::format($product->price) ?></span>
                            </div>
                            <p class="product__desc"><?= e($product->description) ?></p>
                            <form method="post" class="product__form">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="id" value="<?= e($product->id) ?>">
                                <button class="btn btn--add" type="submit">Добавить</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <aside class="sidebar">
        <section class="card cart" id="cart">
            <header class="cart__head">
                <h2>Корзина <span class="badge"><?= $cart->count() ?></span></h2>
                <?php if ($lines !== []): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="clear">
                        <button class="link" type="submit">Очистить</button>
                    </form>
                <?php endif; ?>
            </header>

            <?php if ($lines === []): ?>
                <p class="empty">Пока пусто. Добавьте бургер, комбо и напиток.</p>
            <?php else: ?>
                <ul class="cart__list">
                    <?php foreach ($lines as $line): ?>
                        <li class="cart__item">
                            <div class="cart__info">
                                <span class="cart__title"><?= e($line->title) ?></span>
                                <span class="cart__price"><?= Money::format($line->price) ?></span>
                            </div>
                            <div class="cart__controls">
                                <form method="post">
                                    <input type="hidden" name="action" value="minus">
                                    <input type="hidden" name="id" value="<?= e($line->id) ?>">
                                    <button class="qty" type="submit" aria-label="Уменьшить">−</button>
                                </form>
                                <span class="cart__qty"><?= $line->quantity ?></span>
                                <form method="post">
                                    <input type="hidden" name="action" value="plus">
                                    <input type="hidden" name="id" value="<?= e($line->id) ?>">
                                    <button class="qty" type="submit" aria-label="Увеличить">+</button>
                                </form>
                                <form method="post">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="id" value="<?= e($line->id) ?>">
                                    <button class="qty qty--x" type="submit" aria-label="Удалить">×</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="cart__sum">
                    <span>Товары</span>
                    <strong><?= Money::format($subtotal) ?></strong>
                </div>

                <?php if ($untilFree > 0): ?>
                    <p class="hint">До бесплатной доставки: <?= Money::format($untilFree) ?></p>
                <?php else: ?>
                    <p class="hint hint--ok">Доставка курьером бесплатна 🎉</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <section class="card checkout">
            <h2>Оформление</h2>
            <form method="post">
                <input type="hidden" name="action" value="checkout">

                <div class="methods">
                    <label class="method <?= $method === DeliveryMethod::Courier ? 'is-active' : '' ?>">
                        <input type="radio" name="method" value="courier" <?= $method === DeliveryMethod::Courier ? 'checked' : '' ?>>
                        <span class="method__title">🛵 Курьером</span>
                        <span class="method__desc">от 600 ₸ · 20–40 мин</span>
                    </label>
                    <label class="method <?= $method === DeliveryMethod::Pickup ? 'is-active' : '' ?>">
                        <input type="radio" name="method" value="pickup" <?= $method === DeliveryMethod::Pickup ? 'checked' : '' ?>>
                        <span class="method__title">🏪 Самовывоз</span>
                        <span class="method__desc">0 ₸ · 15–30 мин</span>
                    </label>
                </div>

                <div class="fields" data-block="courier">
                    <label>
                        <span>Город</span>
                        <input name="city" value="<?= e($form['city']) ?>" autocomplete="address-level2">
                    </label>
                    <label>
                        <span>Район</span>
                        <select name="district">
                            <?php foreach (Courier::districts() as $district): ?>
                                <option value="<?= e($district) ?>" <?= $form['district'] === $district ? 'selected' : '' ?>><?= e($district) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Улица</span>
                        <input name="street" value="<?= e($form['street']) ?>" placeholder="Абая" autocomplete="address-line1">
                    </label>
                    <div class="fields__row">
                        <label>
                            <span>Дом</span>
                            <input name="house" value="<?= e($form['house']) ?>" placeholder="15" autocomplete="address-line2">
                        </label>
                        <label>
                            <span>Квартира</span>
                            <input name="apartment" value="<?= e($form['apartment']) ?>" placeholder="12" autocomplete="address-line3">
                        </label>
                    </div>
                </div>

                <div class="fields" data-block="pickup">
                    <label>
                        <span>Пункт выдачи</span>
                        <select name="pickup">
                            <?php foreach (PickupPoint::all() as $point): ?>
                                <option value="<?= e($point->code) ?>" <?= $form['pickup'] === $point->code ? 'selected' : '' ?> <?= $point->isOpen ? '' : 'disabled' ?>>
                                    <?= e($point->title) ?> · <?= e($point->address) ?> · <?= e($point->hours) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <p class="hint">Заказ хранится в пункте 24 часа. Возьмите с собой номер телефона.</p>
                </div>

                <button class="btn btn--primary" type="submit">Рассчитать доставку</button>
            </form>
        </section>

        <?php if ($error !== null): ?>
            <section class="alert" id="result" role="alert">
                <strong>Не получилось рассчитать доставку</strong>
                <p><?= e($error) ?></p>
                <?php if (isset($errorHint)): ?>
                    <p class="alert__hint"><?= e($errorHint) ?></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($quote !== null): ?>
            <section class="card result" id="result">
                <div class="result__top">
                    <img class="result__icon <?= $quote->method === DeliveryMethod::Courier ? 'result__icon--ride' : '' ?>"
                         src="<?= e($quote->method->icon()) ?>" alt="">
                    <div>
                        <span class="result__label"><?= e($quote->method->label()) ?></span>
                        <div class="result__eta"><?= e($quote->eta()) ?></div>
                        <p class="result__status">
                            <span class="dot"></span>
                            <?= $quote->method === DeliveryMethod::Courier
                                ? 'Курьер уже собирает заказ'
                                : 'Заказ ждёт вас на пункте выдачи' ?>
                        </p>
                    </div>
                </div>

                <dl class="result__rows">
                    <div>
                        <dt>Товары</dt>
                        <dd><?= Money::format($subtotal) ?></dd>
                    </div>
                    <div>
                        <dt>Доставка</dt>
                        <dd><?= $quote->isFree() ? 'бесплатно' : Money::format($quote->fee) ?></dd>
                    </div>
                    <div class="result__total">
                        <dt>Итого</dt>
                        <dd><?= e($quote->totalLabel()) ?></dd>
                    </div>
                </dl>

                <p class="result__where">
                    <span>Куда</span>
                    <?= e($place) ?>
                </p>

                <?php if ($logs !== []): ?>
                    <div class="logs">
                        <h3>Журнал (trait LogsActivity)</h3>
                        <ul>
                            <?php foreach ($logs as $entry): ?>
                                <li><?= e($entry) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </aside>
</main>

<footer class="footer">
    <p>Симуляция: заказ не отправляется в реальную службу доставки, все расчёты выполняются локально.</p>
    <p>ЛР №10 · интерфейсы, abstract class, traits, namespaces, исключения</p>
</footer>

<script>
    const radios = document.querySelectorAll('input[name="method"]');
    const blocks = document.querySelectorAll('[data-block]');

    function syncMethod() {
        const checked = document.querySelector('input[name="method"]:checked');

        if (!checked) {
            return;
        }

        blocks.forEach(block => {
            block.hidden = block.dataset.block !== checked.value;
        });

        document.querySelectorAll('.method').forEach(label => {
            label.classList.toggle('is-active', label.querySelector('input').checked);
        });
    }

    radios.forEach(radio => radio.addEventListener('change', syncMethod));
    syncMethod();

    const result = document.getElementById('result');

    if (result) {
        result.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
</script>
</body>
</html>
