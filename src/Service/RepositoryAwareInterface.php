<?php

namespace WebImage\Models\Service;

interface RepositoryAwareInterface
{
	/**
	 * @return RepositoryInterface
	 */
	public function getRepository(): RepositoryInterface;

	/**
	 * @param RepositoryInterface $repository
	 * @return mixed
	 */
	public function setRepository(RepositoryInterface $repository);
}
