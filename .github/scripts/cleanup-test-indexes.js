const { algoliasearch } = require('algoliasearch');

const BATCH_SIZE = 20;

async function main() {
  const client = algoliasearch(process.env.APP_ID, process.env.API_KEY);

  const { items } = await client.listIndices();
  const names = items.map((i) => i.name).filter((n) => n.includes('sf_phpunit_'));

  if (names.length === 0) {
    console.log('Nothing to delete.');
    return;
  }

  console.log(`Deleting ${names.length} indexes in batches of ${BATCH_SIZE}...`);

  const tasks = [];
  for (let i = 0; i < names.length; i += BATCH_SIZE) {
    const batch = names.slice(i, i + BATCH_SIZE);
    const batchTasks = await Promise.all(
      batch.map(async (name) => {
        const { taskID } = await client.deleteIndex({ indexName: name });
        return { name, taskID };
      })
    );
    tasks.push(...batchTasks);
    console.log(`  requested ${Math.min(i + BATCH_SIZE, names.length)}/${names.length}`);
  }

  console.log('Waiting for all tasks to complete...');
  await Promise.all(
    tasks.map(async ({ name, taskID }) => {
      await client.waitForTask({ indexName: name, taskID });
      console.log(`  confirmed ${name}`);
    })
  );

  console.log('All deletes confirmed.');
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
