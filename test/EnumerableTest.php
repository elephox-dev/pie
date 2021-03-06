<?php
declare(strict_types=1);

namespace Elephox\PIE;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Elephox\PIE\Enumerable
 * @covers \Elephox\PIE\RangeIterator
 * @covers \Elephox\PIE\SelectIterator
 * @covers \Elephox\PIE\KeySelectIterator
 * @covers \Elephox\PIE\WhileIterator
 * @covers \Elephox\PIE\ReverseIterator
 * @covers \Elephox\PIE\UniqueByIterator
 * @covers \Elephox\PIE\FlipIterator
 * @covers \Elephox\PIE\DefaultEqualityComparer
 * @covers \Elephox\PIE\IndexOutOfRangeException
 * @covers \Elephox\PIE\EmptySequenceException
 * @covers \Elephox\PIE\AmbiguousMatchException
 * @uses \Elephox\PIE\IsEnumerable
 */
class EnumerableTest extends TestCase
{
	public function testFromThrows(): void
	{
		$this->expectException(InvalidArgumentException::class);
		Enumerable::from(null);
	}

	public function testConstructorClosureThrows(): void
	{
		$this->expectException(InvalidArgumentException::class);
		new Enumerable(function () {
			return null;
		});
	}

	public function testAggregate(): void
	{
		self::assertEquals(
			120,
			Enumerable::range(1, 5)->aggregate(fn($a, $b) => $a * $b, 1)
		);

		self::assertEquals(
			'abc',
			Enumerable::from(['a', 'b', 'c'])->aggregate(fn($a, $b) => $a . $b)
		);
	}

	public function testAll(): void
	{
		self::assertTrue(Enumerable::range(1, 5)->all(fn($x) => $x < 6));
		self::assertFalse(Enumerable::range(1, 5)->all(fn($x) => $x < 4));
	}

	public function testAny(): void
	{
		self::assertTrue(Enumerable::range(1, 3)->any());
		self::assertFalse(Enumerable::empty()->any());
		self::assertTrue(Enumerable::range(1, 3)->any(fn($x) => $x > 1));
		self::assertFalse(Enumerable::range(1, 3)->any(fn($x) => $x > 4));
	}

	public function testAppend(): void
	{
		self::assertEquals(
			[1, 2, 3, 4, 5],
			Enumerable::range(1, 3)->append(4)->append(5)->toArray()
		);
	}

	public function testAppendKeyed(): void
	{
		self::assertEquals(
			[1 => 'a', 2 => 'b', 3 => 'c', 4 => 'd', 5 => 'e'],
			Enumerable::from([1 => 'a', 2 => 'b', 3 => 'c'])->appendKeyed(4, 'd')->appendKeyed(5, 'e')->toArray()
		);
	}

	public function testAverage(): void
	{
		self::assertEquals(2, Enumerable::range(1, 3)->average(fn (int $x) => $x));
	}

	public function testChunk(): void
	{
		self::assertEquals(
			[
				[1, 2, 3],
				[4, 5, 6],
				[7, 8, 9],
			],
			Enumerable::range(1, 9)->chunk(3)->toList()
		);
	}

	public function testConcat(): void
	{
		self::assertEquals(
			[1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
			Enumerable::range(1, 3)
				->concat(Enumerable::range(4, 7), Enumerable::range(8, 10))
				->toList()
		);
	}

	public function testContains(): void
	{
		self::assertTrue(Enumerable::range(1, 10)->contains(5));
		self::assertFalse(Enumerable::range(1, 10)->contains(11));
	}

	public function testCount(): void
	{
		self::assertEquals(10, Enumerable::range(1, 10)->count());
		self::assertEquals(5, Enumerable::range(1, 10)->count(fn(int $x): bool => $x % 2 === 0));
	}

	public function testDistinct(): void
	{
		self::assertEquals(
			[1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
			Enumerable::range(1, 10)->distinct()->toList()
		);

		self::assertEquals(
			[1, 3, 2],
			Enumerable::from([1, 1, 3, 2, 3, 1, 2, 3])->distinct()->toList()
		);
	}

	public function testDistinctBy(): void
	{
		self::assertEquals(
			[1, 2, 3],
			Enumerable::range(1, 10)->distinctBy(fn(int $x): int => $x % 3)->toList()
		);

		self::assertEquals(
			[1, 3, 2],
			Enumerable::from([1, 1, 3, 2, 3, 1, 2, 3])->distinctBy(fn(int $x): int => $x % 3)->toList()
		);
	}

	public function testElementAt(): void
	{
		self::assertEquals(1, Enumerable::range(1, 10)->elementAt(0));
		self::assertEquals(10, Enumerable::range(1, 10)->elementAt(9));
	}

	public function testElementAtOverflow(): void
	{
		$this->expectException(IndexOutOfRangeException::class);

		Enumerable::range(1, 10)->elementAt(10);
	}

	public function testElementAtUnderflow(): void
	{
		$this->expectException(IndexOutOfRangeException::class);

		Enumerable::range(1, 10)->elementAt(-1);
	}

	public function testElementAtOrDefault(): void
	{
		self::assertEquals(1, Enumerable::range(1, 10)->elementAtOrDefault(0, null));
		self::assertEquals(10, Enumerable::range(1, 10)->elementAtOrDefault(9, null));
		self::assertNull(Enumerable::range(1, 10)->elementAtOrDefault(10, null));
		self::assertNull(Enumerable::range(1, 10)->elementAtOrDefault(-1, null));
	}

	public function testExcept(): void
	{
		self::assertEquals(
			[1, 2, 7, 8, 9, 10],
			Enumerable::range(1, 10)->except(Enumerable::range(3, 6))->toList()
		);
	}

	public function testExceptBy(): void
	{
		self::assertEquals(
			[
				['name' => 'alice', 'age' => 5],
				['name' => 'charlie', 'age' => 4],
			],
			Enumerable::from([
					['name' => 'alice', 'age' => 5],
					['name' => 'bob', 'age' => 10],
					['name' => 'charlie', 'age' => 4],
				])
				->exceptBy(
					Enumerable::from([
						['age' => 10]
					]),
					fn(array $x): int => $x['age']
				)
				->toList()
		);
	}

	public function testFirst(): void
	{
		self::assertEquals(1, Enumerable::range(1, 10)->first());
		self::assertEquals(2, Enumerable::range(1, 10)->first(fn(int $x): bool => $x % 2 === 0));

		$this->expectException(EmptySequenceException::class);
		Enumerable::empty()->first();
	}

	public function testFirstOrDefault(): void
	{
		self::assertEquals(1, Enumerable::range(1, 10)->firstOrDefault(null));
		self::assertEquals(2, Enumerable::range(1, 10)->firstOrDefault(null, fn(int $x): bool => $x % 2 === 0));
		self::assertNull(Enumerable::empty()->firstOrDefault(null));
	}

	public function testFlip(): void
	{
		self::assertEquals(
			[
				'a' => 0,
				'b' => 1,
				'c' => 2,
			],
			Enumerable::from(['a', 'b', 'c'])->flip()->toArray()
		);
	}

	public function testIntersect(): void
	{
		self::assertEquals(
			[3, 4, 5],
			Enumerable::range(1, 5)->intersect(Enumerable::range(3, 8))->toList()
		);
	}

	public function testIntersectBy(): void
	{
		self::assertEquals(
			[
				['name' => 'bob', 'age' => 10],
			],
			Enumerable::from([
				['name' => 'alice', 'age' => 5],
				['name' => 'bob', 'age' => 10],
				['name' => 'charlie', 'age' => 4],
			])
				->intersectBy(
					Enumerable::from([
						['age' => 10]
					]),
					fn(array $x): int => $x['age']
				)
				->toList()
		);
	}

	public function testJoin(): void
	{
		self::assertEquals(
			[2, 4, 6, 8, 10],
			Enumerable::range(1, 5)->join(
				Enumerable::range(1, 5),
				fn(int $a) => $a,
				fn(int $b) => $b,
				fn(int $a, int $b) => $a + $b
			)->toList()
		);
	}

	public function testLast(): void
	{
		self::assertEquals(
			'c',
			Enumerable::from(['a', 'b', 'c'])->last()
		);
	}

	public function testLastOrDefault(): void
	{
		self::assertEquals(3, Enumerable::from([1, 2, 3])->lastOrDefault(null));
		self::assertNull(Enumerable::empty()->lastOrDefault(null));
	}

	public function testMax(): void
	{
		self::assertEquals(
			10,
			Enumerable::range(1, 10)->max(fn(int $x) => $x)
		);
	}

	public function testMin(): void
	{
		self::assertEquals(
			1,
			Enumerable::range(1, 3)->min(fn(int $x) => $x)
		);
	}

	public function testOrderBy(): void
	{
		self::assertEquals(
			[1, 2, 3, 4, 5, 6],
			Enumerable::from([6, 2, 5, 1, 4, 3])->orderBy(fn(int $x) => $x)->toList()
		);
	}

	public function testOrderByDescending(): void
	{
		self::assertEquals(
			[
				[
					'name' => 'b',
					'age' => 2,
				],
				[
					'name' => 'a',
					'age' => 1,
				],
			],
			Enumerable::from([
				['name' => 'a', 'age' => 1],
				['name' => 'b', 'age' => 2],
			])->orderByDescending(fn($x) => $x['age'])->toList()
		);
	}

	public function testPrepend(): void
	{
		self::assertEquals(
			[4, 5, 1, 2, 3],
			Enumerable::range(1, 3)->prepend(5)->prepend(4)->toList()
		);
	}

	public function testPrependKeyed(): void
	{
		self::assertEquals(
			['e' => 5, 'd' => 4, 'a' => 1, 'b' => 2, 'c' => 3],
			Enumerable::from(['a' => 1, 'b' => 2, 'c' => 3])->prependKeyed('d', 4)->prependKeyed('e', 5)->toArray()
		);
	}

	public function testReverse(): void
	{
		self::assertEquals(
			[5, 4, 3, 2, 1],
			Enumerable::range(1, 5)->reverse()->toArray()
		);
	}

	public function testSelect(): void
	{
		self::assertEquals(
			[2, 4, 6, 8, 10],
			Enumerable::range(1, 5)
				->select(fn(int $x): int => $x * 2)
				->toList()
		);
	}

	public function testSelectMany(): void
	{
		self::assertEquals(
			[
				1,
				1, 2,
				1, 2, 3,
				1, 2, 3, 4,
				1, 2, 3, 4, 5
			],
			Enumerable::range(1, 5)
				->selectMany(fn(int $x): GenericEnumerable => Enumerable::range(1, $x))
				->toList()
		);
	}

	public function testSequenceEqual(): void
	{
		self::assertTrue(
			Enumerable::range(1, 5)->sequenceEqual(Enumerable::range(1, 5))
		);

		self::assertFalse(
			Enumerable::range(1, 5)->sequenceEqual(Enumerable::range(1, 6))
		);

		self::assertTrue(Enumerable::empty()->sequenceEqual(Enumerable::empty()));
	}

	public function testSingle(): void
	{
		self::assertEquals(
			2,
			Enumerable::from([2])->single()
		);
	}

	public function testSingleMultipleElements(): void
	{
		$this->expectException(AmbiguousMatchException::class);
		Enumerable::from([1, 2])->single();
	}

	public function testSingleNoElements(): void
	{
		$this->expectException(EmptySequenceException::class);
		Enumerable::empty()->single();
	}

	public function testSingleOrDefault(): void
	{
		self::assertEquals(
			1,
			Enumerable::range(1, 5)->singleOrDefault(null, fn(int $x): bool => $x === 1)
		);

		self::assertNull(
			Enumerable::range(1, 5)->singleOrDefault(null, fn(int $x): bool => $x === 6)
		);
	}

	public function testSkip(): void
	{
		self::assertEquals(
			[3, 4, 5],
			Enumerable::range(1, 5)
				->skip(2)
				->toList()
		);
	}

	public function testSkipLast(): void
	{
		self::assertEquals(
			[1, 2, 3],
			Enumerable::range(1, 5)
				->skipLast(2)
				->toList()
		);
	}

	public function testSkipWhile(): void
	{
		self::assertEquals(
			[3, 4, 5],
			Enumerable::range(1,5)
				->skipWhile(fn(int $x): bool => $x < 3)
				->toList()
		);
	}

	public function testTake(): void
	{
		self::assertEquals(
			[0, 1, 2],
			Enumerable::range(0, 6)->take(3)->toList()
		);
	}

	public function testTakeLast(): void
	{
		self::assertEquals(
			[5, 6],
			Enumerable::range(0, 6)->takeLast(2)->toList()
		);
	}

	public function testTakeLastInvalid(): void
	{
		self::assertEquals(
			[],
			Enumerable::range(0, 6)->takeLast(-2)->toList()
		);
	}

	public function testTakeLastEmpty(): void
	{
		self::assertEquals(
			[],
			Enumerable::empty()->takeLast(1)->toList()
		);
	}

	public function testTakeWhile(): void
	{
		self::assertEquals(
			[0, 1, 2],
			Enumerable::range(0, 6)->takeWhile(fn(int $x): bool => $x < 3)->toList()
		);
	}

	public function testUnion(): void
	{
		$a = Enumerable::from([5, 3, 9, 7, 5, 9, 3, 7]);
		$b = Enumerable::from([8, 3, 6, 4, 4, 9, 1, 0]);

		self::assertEquals(
			[5, 3, 9, 7, 8, 6, 4, 1, 0],
			$a->union($b)->toList()
		);
	}

	public function testUnionBy(): void
	{
		$a = Enumerable::from([5, 3, 9, 7, 5, 9, 3, 7]);
		$b = Enumerable::from([8, 3, 6, 4, 4, 9, 1, 0]);

		self::assertEquals(
			[5, 3, 9, 7, 6],
			$a->unionBy($b, fn(int $a) => $a % 5)->toList()
		);
	}

	public function testZip(): void
	{
		self::assertEquals(
			[
				[1, 4],
				[2, 5],
				[3, 6],
			],
			Enumerable::range(1, 3)->zip(Enumerable::range(4, 6))->toList()
		);
	}
}
